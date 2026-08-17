/**
 * Count-up animation for the big-numbers band.
 *
 * The figures themselves are page content, not values held in here. Each number
 * is written into its paragraph in the block editor and ships in the server
 * HTML, so a crawler, a feed reader or a visitor with JavaScript disabled reads
 * the real figure. This module only replays it as an animation: it reads the
 * rendered number as its target and counts up to it when the band scrolls into
 * view.
 *
 * That ordering is the whole point. The previous version started the band at a
 * literal 0 in the content and only fired on a `scroll` event, so anything that
 * renders the page without scrolling it — Googlebot included — indexed a school
 * claiming zero students. Nothing here writes a value until an animation is
 * already running, so a zero can never be the state the page gets stuck on.
 */

// Set in the block editor as the paragraph's Additional CSS class, matching the
// `.slide-up` / `.zoom-element` convention used elsewhere in this folder.
const SELECTOR = '.count-up';

const DURATION = 1000;

// Splits "1,000+" into an optional prefix, the digits, and an optional suffix,
// so a "+" or a "€" typed into the content survives the animation.
const PARTS = /^(\D*)([\d,]+)(\D*)$/;

/**
 * Read the target figure out of an element's rendered text.
 *
 * @param {HTMLElement} element The element holding the number.
 * @return {{value: number, prefix: string, suffix: string, grouped: boolean}|null}
 *         The parsed figure, or null when the text is not a number.
 */
function parseTarget(element) {
  const parts = PARTS.exec(element.textContent.trim());
  if (!parts) {
    return null;
  }

  const digits = parts[2].replace(/,/g, '');
  const value = Number(digits);
  if (!Number.isFinite(value)) {
    return null;
  }

  return {
    value: value,
    prefix: parts[1],
    suffix: parts[3],
    // Keep the thousands separator only if the content author used one.
    grouped: parts[2].includes(','),
  };
}

/**
 * Format a frame of the animation the way the content was written.
 *
 * @param {number} current The number to render.
 * @param {Object} target  The parsed target from parseTarget().
 * @return {string} The text to write.
 */
function format(current, target) {
  const digits = target.grouped ? current.toLocaleString('en-GB') : String(current);

  return target.prefix + digits + target.suffix;
}

/**
 * Count an element from zero up to its target.
 *
 * @param {HTMLElement} element The element to animate.
 * @param {Object}      target  The parsed target from parseTarget().
 */
function countUp(element, target) {
  const startTime = performance.now();

  function frame(currentTime) {
    const progress = Math.min((currentTime - startTime) / DURATION, 1);
    element.textContent = format(Math.floor(progress * target.value), target);

    if (progress < 1) {
      requestAnimationFrame(frame);
    }
  }

  requestAnimationFrame(frame);
}

export function initCountUp() {
  const elements = Array.from(document.querySelectorAll(SELECTOR));
  if (!elements.length) {
    return;
  }

  // Someone who has asked for less motion keeps the figure already on screen.
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  const targets = new Map();
  elements.forEach(function(element) {
    const target = parseTarget(element);
    if (target) {
      targets.set(element, target);
    }
  });

  if (!targets.size) {
    return;
  }

  const observer = new IntersectionObserver(
    function(entries) {
      entries.forEach(function(entry) {
        if (!entry.isIntersecting) {
          return;
        }

        // Each figure animates once. Unobserving first means a re-entry mid
        // animation cannot restart it and visibly knock the number back down.
        observer.unobserve(entry.target);
        countUp(entry.target, targets.get(entry.target));
      });
    },
    { threshold: 0.5 }
  );

  targets.forEach(function(target, element) {
    observer.observe(element);
  });
}

document.addEventListener('DOMContentLoaded', initCountUp);
