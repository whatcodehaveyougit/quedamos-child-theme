/**
 * Progressive enhancement for the blog's category filter.
 *
 * The pills PHP renders are ordinary links to ordinary category archive URLs, so
 * the filter already works with this file removed — the URL is the source of
 * truth and this is an enhancement over it. What the enhancement does is fetch
 * that same URL, take the listing out of the response and swap it in, so the
 * server and the AJAX path always render through the same block template. That
 * is why there is no REST route and no admin-ajax action here: a second endpoint
 * would mean a second copy of the card markup, and two copies drift.
 *
 * Pagination links inside the swapped listing are handled by the same delegated
 * listener, so they keep working after a filter change.
 */

const PAGE_SELECTOR = '.blog-page';
const FILTER_SELECTOR = '.blog-filter-section';
const LISTING_SELECTOR = '.blog-listing-section';
const PILL_SELECTOR = '.category-filter__pill';
const PAGINATION_SELECTOR = '.blog-pagination a[href]';
const CURRENT_PILL_SELECTOR = '.category-filter__pill--current';

/**
 * Whether a click should be left to the browser.
 *
 * A modified click is the reader asking for a new tab or a download, and a
 * cross-origin href is not our listing — both must navigate normally.
 *
 * @param {MouseEvent} event The click event.
 * @param {HTMLAnchorElement} link The link that was clicked.
 * @return {boolean} True when the browser should handle the click itself.
 */
function shouldIgnore(event, link) {
  if (event.defaultPrevented || event.button !== 0) {
    return true;
  }

  if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
    return true;
  }

  if (link.target && link.target !== '_self') {
    return true;
  }

  return link.origin !== window.location.origin;
}

/**
 * Replace the filter row and the listing with the ones at a URL.
 *
 * On any failure the page navigates to the URL for real, so a reader never ends
 * up looking at a stale list with a URL that promises something else.
 *
 * @param {string} url The URL to render.
 * @param {boolean} moveFocus Whether to move focus after the swap.
 * @return {Promise<boolean>} Whether the swap happened.
 */
function swapTo(url, moveFocus) {
  const listing = document.querySelector(LISTING_SELECTOR);
  if (!listing) {
    return Promise.resolve(false);
  }

  listing.setAttribute('aria-busy', 'true');

  return fetch(url, { credentials: 'same-origin' })
    .then(function(response) {
      if (!response.ok) {
        throw new Error('unavailable');
      }

      return response.text();
    })
    .then(function(html) {
      const doc = new DOMParser().parseFromString(html, 'text/html');
      const nextFilter = doc.querySelector(FILTER_SELECTOR);
      const nextListing = doc.querySelector(LISTING_SELECTOR);

      if (!nextFilter || !nextListing) {
        throw new Error('unrecognised');
      }

      const currentFilter = document.querySelector(FILTER_SELECTOR);
      const currentListing = document.querySelector(LISTING_SELECTOR);

      if (currentFilter) {
        currentFilter.replaceWith(nextFilter);
      }

      if (currentListing) {
        currentListing.replaceWith(nextListing);
      }

      if (doc.title) {
        document.title = doc.title;
      }

      if (moveFocus) {
        focusCurrentPill();
      }

      return true;
    })
    .catch(function() {
      listing.removeAttribute('aria-busy');
      window.location.assign(url);

      return false;
    });
}

/**
 * Put focus on the pill that is now active.
 *
 * The results the reader just asked for start immediately below it, so this both
 * gives the keyboard a sensible place to carry on from and keeps the filter row
 * on screen — which matters most on a phone, where the swapped list can
 * otherwise leave the row scrolled away above.
 */
function focusCurrentPill() {
  const pill = document.querySelector(CURRENT_PILL_SELECTOR);
  if (!pill) {
    return;
  }

  pill.focus();
  pill.scrollIntoView({ block: 'nearest' });
}

export function initBlogFilter() {
  const page = document.querySelector(PAGE_SELECTOR);
  if (!page) {
    return;
  }

  // Delegated from the page wrapper, which is never replaced — the pills and the
  // pagination links both live inside regions that are.
  page.addEventListener('click', function(event) {
    const target = event.target;
    if (!target || typeof target.closest !== 'function') {
      return;
    }

    const link = target.closest(PILL_SELECTOR + ', ' + PAGINATION_SELECTOR);
    if (!link || !page.contains(link)) {
      return;
    }

    if (shouldIgnore(event, link)) {
      return;
    }

    event.preventDefault();

    const url = link.href;

    swapTo(url, true).then(function(swapped) {
      if (swapped && window.location.href !== url) {
        window.history.pushState({ quedamosBlogFilter: true }, '', url);
      }
    });
  });

  // Back and forward re-render from the URL the browser restored. Focus is left
  // alone here: the reader moved through history, they did not press anything.
  window.addEventListener('popstate', function() {
    if (!document.querySelector(FILTER_SELECTOR)) {
      return;
    }

    swapTo(window.location.href, false);
  });
}

document.addEventListener('DOMContentLoaded', initBlogFilter);
