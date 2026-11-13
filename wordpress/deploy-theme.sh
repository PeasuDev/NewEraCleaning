#!/usr/bin/env bash
#
# Push the theme straight to the WordPress install over SSH — no zip, no wp-admin.
#
# Connection details live in deploy.env (gitignored), never in this file. Copy
# deploy.env.example and fill it from Site Tools -> Devs -> SSH Keys Manager.
#
#   ./deploy-theme.sh                 push PHP/CSS/JS only (the usual case, ~400K)
#   ./deploy-theme.sh --all           push everything including assets/ (~2.9M)
#   ./deploy-theme.sh --dry-run       list what would go, touch nothing
#   ./deploy-theme.sh inc/mail.php    push named files only (paths relative to the theme)
#
# This deploys to LIVE. Every run archives the current remote theme first, outside
# the web root, and prints the exact command to put it back.
#
set -euo pipefail

cd "$(dirname "$0")"

THEME_DIR="newera-steam-cleaning"
BACKUP_KEEP=10

[[ -f deploy.env ]] || { echo "deploy.env not found. Copy deploy.env.example and fill it in." >&2; exit 1; }
# shellcheck disable=SC1091
source ./deploy.env

for var in SG_HOST SG_USER SG_PORT SG_THEMES SG_KEY; do
	[[ -n "${!var:-}" ]] || { echo "deploy.env is missing $var" >&2; exit 1; }
done

SSH=(ssh -i "$SG_KEY" -p "$SG_PORT" -o StrictHostKeyChecking=accept-new "$SG_USER@$SG_HOST")
DOCROOT="$(dirname "$(dirname "$SG_THEMES")")"
REMOTE_THEME="$SG_THEMES/$THEME_DIR"
BACKUPS="~/newera-theme-backups"

mode="code"
files=()
for arg in "$@"; do
	case "$arg" in
		--all)     mode="all" ;;
		--dry-run) mode="${mode}-dry"; DRY=1 ;;
		-*)        echo "unknown option: $arg" >&2; exit 1 ;;
		*)         files+=("$arg"); mode="explicit" ;;
	esac
done

# --- what to send -----------------------------------------------------------

if [[ ${#files[@]} -gt 0 ]]; then
	for f in "${files[@]}"; do
		[[ -f "$THEME_DIR/$f" ]] || { echo "no such file in the theme: $f" >&2; exit 1; }
	done
	payload=("${files[@]}")
else
	if [[ "$mode" == all* ]]; then
		mapfile -t payload < <(cd "$THEME_DIR" && find . -type f ! -name '.*' | sed 's|^\./||' | sort)
	else
		mapfile -t payload < <(cd "$THEME_DIR" && find . -type f \( -name '*.php' -o -name '*.css' -o -name '*.js' \) | sed 's|^\./||' | sort)
	fi
fi

echo "target : $SG_USER@$SG_HOST:$REMOTE_THEME"
echo "sending: ${#payload[@]} files ($(cd "$THEME_DIR" && du -ch "${payload[@]}" | tail -1 | cut -f1))"

if [[ -n "${DRY:-}" ]]; then
	printf '  %s\n' "${payload[@]}"
	echo "(dry run — nothing sent)"
	exit 0
fi

# --- preflight --------------------------------------------------------------

"${SSH[@]}" "test -d '$REMOTE_THEME'" || {
	echo "The theme is not installed at $REMOTE_THEME." >&2
	echo "This script updates an existing install; do the first one from the zip." >&2
	exit 1
}

# --- back the current theme up, outside the web root ------------------------
#
# Not into wp-content/themes: a copy there carries its own style.css and shows up
# in Appearance -> Themes as a second, near-identical theme. Nor anywhere under
# public_html, where the archive would be downloadable by anyone who guessed the URL.

stamp="$("${SSH[@]}" "date +%Y%m%d-%H%M%S")"
archive="$BACKUPS/$THEME_DIR-$stamp.tar.gz"
"${SSH[@]}" "mkdir -p $BACKUPS && tar czf $archive -C '$SG_THEMES' '$THEME_DIR'"
echo "backup : $archive"

# --- send -------------------------------------------------------------------
#
# One tar stream rather than per-file scp: 30 round trips over a home connection is
# most of the wall clock. Deliberately no -p — letting mtimes land as "now" is what
# tells PHP's opcache the bytecode it has cached is stale.

list="$(mktemp)"
trap 'rm -f "$list"' EXIT
printf '%s\n' "${payload[@]/#/$THEME_DIR/}" > "$list"

tar czf - -T "$list" | "${SSH[@]}" "tar xzf - -C '$SG_THEMES'"

# --- verify -----------------------------------------------------------------
#
# Compare checksums rather than trusting tar's exit code: a partial extract, a
# read-only file or a full disk can all end zero.

local_sums="$(cd "$THEME_DIR" && md5sum "${payload[@]}" | sort)"
remote_sums="$("${SSH[@]}" "cd '$REMOTE_THEME' && md5sum $(printf "'%s' " "${payload[@]}")" | sort)"

if [[ "$local_sums" != "$remote_sums" ]]; then
	echo "MISMATCH — what is on the server is not what was sent:" >&2
	diff <(echo "$local_sums") <(echo "$remote_sums") >&2 || true
	echo "Restore with: ssh ... 'tar xzf $archive -C $SG_THEMES'" >&2
	exit 1
fi
echo "verified: ${#payload[@]}/${#payload[@]} files match by checksum"

# --- clear the caches that would serve the old code -------------------------

"${SSH[@]}" "cd '$DOCROOT' && wp sg purge" >/dev/null 2>&1 && echo "cache  : SG cache purged" \
	|| echo "cache  : 'wp sg purge' unavailable — purge from the admin toolbar if a change does not show"

active="$("${SSH[@]}" "cd '$DOCROOT' && wp theme list --status=active --field=name" 2>/dev/null || echo "?")"
echo "active : $active"
[[ "$active" == "$THEME_DIR" ]] || echo "         ^ note: the deployed theme is NOT the active one"

# --- prune old backups ------------------------------------------------------

"${SSH[@]}" "ls -1t $BACKUPS/$THEME_DIR-*.tar.gz 2>/dev/null | tail -n +$((BACKUP_KEEP + 1)) | xargs -r rm --"

echo "rollback: ssh -i \$SG_KEY -p $SG_PORT $SG_USER@$SG_HOST \"tar xzf $archive -C '$SG_THEMES'\""
