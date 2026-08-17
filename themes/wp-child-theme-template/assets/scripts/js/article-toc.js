/**
 * Active-link highlighting for the article table of contents.
 *
 * Scope is deliberately just the highlight. Scrolling to a heading is pure CSS
 * (scroll-behavior: smooth plus scroll-margin-top on the headings), so there is
 * no click handler here and the links keep working with JavaScript disabled.
 *
 * An IntersectionObserver watches every heading the TOC points at and marks the
 * topmost one currently in view.
 */

// Matches the 992px breakpoint in components/article-toc.scss, above which the
// TOC is a sticky column rather than an accordion.
const DESKTOP_QUERY = '(min-width: 992px)';

/**
 * Keep the TOC expanded on desktop.
 *
 * Above the breakpoint the summary is styled as a plain label with
 * pointer-events disabled, so a <details> left closed there could never be
 * reopened. Collapsing someone did on a narrow window must not survive a
 * resize.
 *
 * @param {HTMLDetailsElement} toc The TOC element.
 */
function keepOpenOnDesktop(toc) {
  const desktop = window.matchMedia(DESKTOP_QUERY);

  function sync() {
    if (desktop.matches) {
      toc.open = true;
    }
  }

  sync();
  desktop.addEventListener('change', sync);
}

export function initArticleToc() {
  const toc = document.querySelector('.article-toc');
  if (!toc) {
    return;
  }

  keepOpenOnDesktop(toc);

  const links = Array.from(toc.querySelectorAll('[data-toc-link]'));
  if (!links.length) {
    return;
  }

  // Resolve each link to its heading, dropping any that don't match. A link
  // without a target would otherwise sit permanently inactive.
  const sections = links
    .map(function(link) {
      return { link: link, target: document.getElementById(link.getAttribute('data-toc-link')) };
    })
    .filter(function(pair) {
      return pair.target;
    });

  if (!sections.length) {
    return;
  }

  function setActive(target) {
    sections.forEach(function(pair) {
      pair.link.classList.toggle('is-active', pair.target === target);
    });
  }

  const observer = new IntersectionObserver(
    function(entries) {
      const visible = entries
        .filter(function(entry) {
          return entry.isIntersecting;
        })
        .sort(function(a, b) {
          return a.boundingClientRect.top - b.boundingClientRect.top;
        });

      if (visible[0]) {
        setActive(visible[0].target);
      }
    },
    {
      // Only the upper part of the viewport counts as "in view", so the active
      // link tracks the section being read rather than one leaving the bottom.
      // The header here is static, so no sticky-header offset is needed.
      rootMargin: '-16px 0px -70% 0px',
      threshold: 0,
    }
  );

  sections.forEach(function(pair) {
    observer.observe(pair.target);
  });

  // Highlight the first section before any scrolling happens.
  setActive(sections[0].target);
}

document.addEventListener('DOMContentLoaded', initArticleToc);
