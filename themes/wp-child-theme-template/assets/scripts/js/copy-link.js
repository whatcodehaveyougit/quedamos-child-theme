/**
 * Copy-to-clipboard for the article author bar's copy button.
 *
 * The URL copied is the one PHP put on the button's data-copy-url — WordPress's
 * canonical permalink — never window.location, which carries whatever tracking
 * parameters the visitor arrived with.
 *
 * navigator.clipboard needs a secure context (https, or localhost in
 * development). The textarea fallback covers everything else, so the button
 * never silently does nothing.
 */

const COPIED_CLASS = 'is-copied';
const FEEDBACK_MS = 2000;
const COPIED_LABEL = 'Copied';

/**
 * Write text to the clipboard.
 *
 * @param {string} text The text to copy.
 * @return {Promise<boolean>} Whether the copy succeeded.
 */
function writeToClipboard(text) {
  if (navigator.clipboard && window.isSecureContext) {
    return navigator.clipboard.writeText(text).then(function() {
      return true;
    }).catch(function() {
      return false;
    });
  }

  const field = document.createElement('textarea');
  field.value = text;
  field.setAttribute('readonly', '');
  // Off-screen rather than display:none — a hidden field cannot be selected.
  field.style.position = 'fixed';
  field.style.left = '-9999px';
  document.body.appendChild(field);
  field.select();

  let copied = false;
  try {
    copied = document.execCommand('copy');
  } catch (error) {
    copied = false;
  }

  document.body.removeChild(field);

  return Promise.resolve(copied);
}

/**
 * Show the "Copied" confirmation, then clear it.
 *
 * @param {HTMLButtonElement} button The button that was pressed.
 */
function confirmCopy(button) {
  const feedback = button.querySelector('.article-author__copy-feedback');

  window.clearTimeout(button.copyTimer);
  button.classList.add(COPIED_CLASS);

  if (feedback) {
    feedback.textContent = COPIED_LABEL;
  }

  button.copyTimer = window.setTimeout(function() {
    button.classList.remove(COPIED_CLASS);

    if (feedback) {
      feedback.textContent = '';
    }
  }, FEEDBACK_MS);
}

export function initCopyLink() {
  const buttons = document.querySelectorAll('.article-author__copy');
  if (!buttons.length) {
    return;
  }

  buttons.forEach(function(button) {
    button.addEventListener('click', function() {
      const url = button.dataset.copyUrl;
      if (!url) {
        return;
      }

      writeToClipboard(url).then(function(copied) {
        if (copied) {
          confirmCopy(button);
        }
      });
    });
  });
}

document.addEventListener('DOMContentLoaded', initCopyLink);
