<?php
/**
 * Site header.
 *
 * Ported from the header half of Layout/PublicLayout.razor.
 *
 * The viewport tag deliberately omits `maximum-scale` and `user-scalable=no`:
 * the live site blocks pinch-zoom, which fails WCAG 1.4.4.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#content">Skip to content</a>

<div class="public-shell">
	<header class="public-header">
		<div class="public-header-inner">
			<a class="public-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img src="<?php echo esc_url( newera_logo_url() ); ?>"
					alt="<?php echo newera_attr_text( newera_business_name() ); ?> logo"
					class="public-brand-logo" width="160" height="46" />
				<?php $newera_name_parts = newera_business_name_parts(); ?>
				<span class="public-brand-text">
					<span class="public-brand-name"><?php echo esc_html( $newera_name_parts[0] ); ?></span>
					<?php if ( $newera_name_parts[1] ) : ?>
						<span class="public-brand-sub"><?php echo esc_html( $newera_name_parts[1] ); ?></span>
					<?php endif; ?>
				</span>
			</a>

			<?php
			/*
			 * The mobile menu is driven by this checkbox and the sibling label
			 * below it — no JavaScript. The CSS selector is
			 * `.public-nav-toggle:checked ~ .public-nav`, so the input must stay
			 * before the <nav> and inside the same parent.
			 */
			?>
			<input type="checkbox" id="public-nav-toggle" class="public-nav-toggle" />
			<label for="public-nav-toggle" class="public-nav-burger">
				<span class="screen-reader-text">Toggle navigation</span>
				<span aria-hidden="true"></span>
				<span aria-hidden="true"></span>
				<span aria-hidden="true"></span>
			</label>

			<nav class="public-nav" aria-label="Primary">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => '',
						'depth'          => 1,
						'fallback_cb'    => 'newera_default_nav',
					)
				);
				?>
			</nav>

			<a class="public-call" href="<?php echo esc_url( newera_phone( 'tel' ) ); ?>">
				<?php echo newera_icon( 'bi-telephone-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="public-call-text">
					<span class="public-call-label">Call for a free quote</span>
					<span class="public-call-number"><?php echo esc_html( newera_phone() ); ?></span>
				</span>
			</a>
		</div>
	</header>

	<main class="public-main" id="content">
