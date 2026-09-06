// Scroll helpers for the public marketing chrome.
//
// The WordPress theme needs none of this: every nav item there is a full page
// load, so the browser lands the visitor at the top of the document for free.
// Blazor navigates with history.pushState instead, which leaves the viewport
// exactly where it was — including when you pick the page you are already on.
// PublicLayout calls these from its LocationChanged handler to put that back.

/**
 * Jump to the top of the document.
 *
 * Deliberately 'instant', for two reasons. It is what a real page load does, so
 * the preview matches the WordPress site it stands in for; and public.css sets
 * `scroll-behavior: smooth` on <html>, so without an explicit behavior this
 * would animate the whole way up from the footer on every navigation.
 */
export function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'instant' });
}

/**
 * Bring the quote form into view. Used when the footer's "Request a Free Quote"
 * button is pressed while the visitor is already on the quote page, where a
 * plain navigation would do nothing visible.
 *
 * No `behavior` here, unlike scrollToTop: this is movement within a page the
 * visitor is already reading, so it should animate, and public.css already says
 * so with `scroll-behavior: smooth` on <html>. Leaving the choice to the
 * stylesheet keeps it in one place and means a future prefers-reduced-motion
 * rule there would be honoured for free.
 *
 * Returns false when there is no form on the page so the caller can fall back
 * to scrollToTop rather than leaving the button looking broken.
 */
export function scrollToQuoteForm() {
    // .quote-form-wrap carries scroll-margin-top, which keeps the form clear of
    // the sticky header — same trick .gallery-tile uses for the lightbox.
    const form = document.querySelector('.quote-form-wrap');
    if (!form) {
        return false;
    }

    form.scrollIntoView({ block: 'start' });
    return true;
}
