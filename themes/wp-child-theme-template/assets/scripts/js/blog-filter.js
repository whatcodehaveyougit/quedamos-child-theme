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
 *
 * Back and forward restore from markup this page load already rendered rather
 * than from the network. Once the URL is pushed, the browser can move between
 * entries faster than a fetch can answer, and a listing that contradicts the
 * address bar — even for a few hundred milliseconds — is the reader being told
 * two different things at once.
 */

const PAGE_SELECTOR = '.blog-page';
const FILTER_SELECTOR = '.blog-filter-section';
const LISTING_SELECTOR = '.blog-listing-section';
const PILL_SELECTOR = '.category-filter__pill';
const PAGINATION_SELECTOR = '.blog-pagination a[href]';
const CURRENT_PILL_SELECTOR = '.category-filter__pill--current';

// Incremented per request so a response that has been overtaken can be dropped.
let latestRequest = 0;

// The markup of every listing URL this page load has shown, keyed by URL, so a
// history entry can be put back without asking the server for it again. It only
// ever holds the URLs the reader actually visited in this document, and it goes
// with the document.
const rendered = new Map();

// Which entry the DOM is showing. A popstate that lands back on it — a fragment
// link, say — has nothing to restore, so the current markup is left alone.
let currentKey = '';

/**
 * The cache key for a URL: the URL without its fragment.
 *
 * A fragment moves the reader within a listing, it does not change which posts
 * that listing holds, so both spellings must find the same stored markup.
 *
 * @param {string} url The URL to key.
 * @return {string} The key.
 */
function cacheKey(url) {
  const parsed = new URL(url, window.location.href);
  parsed.hash = '';

  return parsed.href;
}

/**
 * Take over from any request still in flight.
 *
 * A fetch already running belongs to a click the reader has since navigated away
 * from, so its response must not be allowed to land on top of what replaced it —
 * and the busy state it put on the listing has to come off with it.
 */
function cancelInFlight() {
  latestRequest += 1;

  const listing = document.querySelector(LISTING_SELECTOR);
  if (listing) {
    listing.removeAttribute('aria-busy');
  }
}

/**
 * Store the markup now on screen against the URL now in the address bar.
 *
 * Called after the URL has moved, never before, so the two always match.
 */
function remember() {
  const filter = document.querySelector(FILTER_SELECTOR);
  const listing = document.querySelector(LISTING_SELECTOR);
  if (!filter || !listing) {
    return;
  }

  currentKey = cacheKey(window.location.href);

  rendered.set(currentKey, {
    filter: filter.outerHTML,
    listing: listing.outerHTML,
    title: document.title,
  });
}

/**
 * Turn stored markup back into an element.
 *
 * @param {string} html The markup.
 * @return {Element|null} The element it describes.
 */
function elementFrom(html) {
  const holder = document.createElement('template');
  holder.innerHTML = html;

  return holder.content.firstElementChild;
}

/**
 * Put a filter row and a listing on the page in place of the ones there now.
 *
 * @param {Element} nextFilter The filter row to show.
 * @param {Element} nextListing The listing to show.
 * @param {string} title The document title that goes with them.
 */
function render(nextFilter, nextListing, title) {
  const currentFilter = document.querySelector(FILTER_SELECTOR);
  const currentListing = document.querySelector(LISTING_SELECTOR);

  if (currentFilter) {
    currentFilter.replaceWith(nextFilter);
  }

  if (currentListing) {
    currentListing.replaceWith(nextListing);
  }

  if (title) {
    document.title = title;
  }
}

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

  // Two pills tapped in quick succession leave two requests in flight, and they
  // can come back in either order. Only the newest one is allowed to land.
  cancelInFlight();
  const thisRequest = latestRequest;

  listing.setAttribute('aria-busy', 'true');

  return fetch(url, { credentials: 'same-origin' })
    .then(function(response) {
      if (!response.ok) {
        throw new Error('unavailable');
      }

      return response.text();
    })
    .then(function(html) {
      if (thisRequest !== latestRequest) {
        return false;
      }

      const doc = new DOMParser().parseFromString(html, 'text/html');
      const nextFilter = doc.querySelector(FILTER_SELECTOR);
      const nextListing = doc.querySelector(LISTING_SELECTOR);

      if (!nextFilter || !nextListing) {
        throw new Error('unrecognised');
      }

      render(nextFilter, nextListing, doc.title);

      if (moveFocus) {
        focusCurrentPill();
      }

      return true;
    })
    .catch(function() {
      if (thisRequest !== latestRequest) {
        return false;
      }

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

  // The entry the reader landed on. Without it, going back to the page they
  // started from is the one hop that has nothing to restore.
  remember();

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
      if (!swapped) {
        return;
      }

      if (window.location.href !== url) {
        window.history.pushState({ quedamosBlogFilter: true }, '', url);
      }

      // After the push, so the markup is stored against the URL it belongs to.
      remember();
    });
  });

  // Back and forward. Focus is left alone: the reader moved through history,
  // they did not press anything.
  window.addEventListener('popstate', function() {
    if (!document.querySelector(FILTER_SELECTOR)) {
      return;
    }

    // The browser has already moved the URL, so whatever was being fetched for
    // the entry we just left is no longer wanted.
    cancelInFlight();

    const key = cacheKey(window.location.href);
    if (key === currentKey) {
      return;
    }

    const entry = rendered.get(key);
    if (entry) {
      render(elementFrom(entry.filter), elementFrom(entry.listing), entry.title);
      currentKey = key;

      return;
    }

    // Nothing stored for this entry — a session the browser restored, say. Fall
    // back to the network. No pushState on this path: the browser has moved
    // already, and pushing here would bury the entry the reader came from.
    swapTo(window.location.href, false).then(function(swapped) {
      if (swapped) {
        remember();
      }
    });
  });
}

document.addEventListener('DOMContentLoaded', initBlogFilter);
