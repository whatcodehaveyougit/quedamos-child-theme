/**
 * Panel open/close behaviour for the quedamos/site-navigation block.
 *
 * The block owns its own markup, so everything core's navigation block used to
 * provide through the Interactivity API is rebuilt here: aria-expanded on the
 * toggle, focus moved into the panel and handed back on close, Tab trapped
 * inside while open, Esc to close, and the page behind held still.
 *
 * Only the mobile half needs script. The desktop row is plain links and has no
 * behaviour to wire — everything below concerns the panel.
 *
 * Visibility itself is CSS — this module only toggles the state class and the
 * ARIA attributes, so the panel's transition (and its suppression under
 * prefers-reduced-motion) stays in one place, in site-navigation.scss.
 */

// Set on <html> rather than <body> so the scroll lock catches whichever element
// the browser scrolls. Mirrored in site-navigation.scss.
const DOCUMENT_OPEN_CLASS = 'has-site-navigation-open';
const OPEN_CLASS = 'is-open';

// Everything the browser will let Tab reach inside the panel. Negative
// tabindex is excluded because it is reachable by script only.
const FOCUSABLE = [
  'a[href]',
  'button:not([disabled])',
  'input:not([disabled])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',');

/**
 * The panel's focusable children, in document order.
 *
 * Recomputed on each Tab rather than cached: a hidden element has no layout, and
 * the list is short enough that querying it is cheaper than tracking changes.
 *
 * @param {HTMLElement} panel The dialog element.
 * @returns {HTMLElement[]} The focusable elements inside it.
 */
function focusableWithin(panel) {
  return Array.from(panel.querySelectorAll(FOCUSABLE)).filter(function(element) {
    return element.offsetWidth > 0 || element.offsetHeight > 0;
  });
}

/**
 * Wire one site navigation block's panel up.
 *
 * @param {HTMLElement} root The block wrapper.
 */
function setupNavigation(root) {
  const toggle = root.querySelector('[data-site-navigation-toggle]');
  const overlay = root.querySelector('[data-site-navigation-overlay]');
  const panel = root.querySelector('[data-site-navigation-panel]');

  if (!toggle || !overlay || !panel) {
    return;
  }

  let isOpen = false;

  function open() {
    if (isOpen) {
      return;
    }

    isOpen = true;
    overlay.classList.add(OPEN_CLASS);
    document.documentElement.classList.add(DOCUMENT_OPEN_CLASS);
    toggle.setAttribute('aria-expanded', 'true');

    // The close button is the first thing in the panel, so focusing the first
    // focusable child lands on the way out rather than on a link.
    const focusable = focusableWithin(panel);
    if (focusable.length) {
      focusable[0].focus();
    }
  }

  function close(returnFocus) {
    if (!isOpen) {
      return;
    }

    isOpen = false;
    overlay.classList.remove(OPEN_CLASS);
    document.documentElement.classList.remove(DOCUMENT_OPEN_CLASS);
    toggle.setAttribute('aria-expanded', 'false');

    if (returnFocus) {
      toggle.focus();
    }
  }

  function trapTab(event) {
    const focusable = focusableWithin(panel);
    if (!focusable.length) {
      return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
      return;
    }

    if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  toggle.addEventListener('click', function() {
    if (isOpen) {
      close(true);
      return;
    }

    open();
  });

  root.querySelectorAll('[data-site-navigation-close], [data-site-navigation-scrim]').forEach(function(control) {
    control.addEventListener('click', function() {
      close(true);
    });
  });

  // Links navigate on their own. Closing here covers the cases a page load
  // would not: a same-page anchor, and the gap before a slow response arrives.
  panel.querySelectorAll('a[href]').forEach(function(link) {
    link.addEventListener('click', function() {
      close(false);
    });
  });

  // A viewport dragged up past the breakpoint hides the panel by CSS but leaves
  // the state behind it — the scroll lock still on, aria-expanded still true, and
  // focus potentially parked on a link that is now display:none. Closing on that
  // crossing keeps the two halves of the block from disagreeing. Focus is not
  // returned to the toggle, because the toggle is itself hidden at this width.
  const desktop = window.matchMedia('(min-width: 768px)');
  desktop.addEventListener('change', function(event) {
    if (event.matches) {
      close(false);
    }
  });

  document.addEventListener('keydown', function(event) {
    if (!isOpen) {
      return;
    }

    if ('Escape' === event.key) {
      close(true);
      return;
    }

    if ('Tab' === event.key) {
      trapTab(event);
    }
  });
}

export function initSiteNavigation() {
  const navigations = document.querySelectorAll('.site-navigation');
  if (!navigations.length) {
    return;
  }

  navigations.forEach(setupNavigation);
}

document.addEventListener('DOMContentLoaded', initSiteNavigation);
