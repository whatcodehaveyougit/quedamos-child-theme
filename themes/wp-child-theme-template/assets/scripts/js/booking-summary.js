/**
 * Booking summary — keep the totals in step with the participant count.
 *
 * The price and the currency symbol come from the localised quedamosBooking
 * object, not from the card's own text. The card used to be read back with a
 * `/£(\d+)/` regex, which silently pinned the whole page to pounds: a euro
 * course would have matched nothing and priced every booking at zero.
 *
 * Amounts are formatted the way the PHP view formats them — symbol first, whole
 * numbers, comma thousands separator — so the figures the server printed and the
 * ones typed into the participants field never render in two different shapes.
 */

const CARD_SELECTOR = '.booking-summary-card';
const PARTICIPANTS_INPUT_SELECTOR = '.qd-participants input[type="number"]';
const PARTICIPANTS_ROW_SELECTOR = '.participants';
const SUBTOTAL_SELECTOR = '.subtotal-row span:last-child';
const TOTAL_SELECTOR = '.total-row span:last-child';

/**
 * Format an amount for display, matching PHP's number_format( $amount, 0 ).
 *
 * @param {number} amount The amount to format.
 * @param {string} symbol The currency symbol to prefix.
 * @return {string} The formatted amount.
 */
function formatAmount(amount, symbol) {
  return symbol + Math.round(amount).toLocaleString('en-GB');
}

export function initBookingSummary() {
  const card = document.querySelector(CARD_SELECTOR);
  if (!card) {
    return;
  }

  const input = document.querySelector(PARTICIPANTS_INPUT_SELECTOR);
  if (!input) {
    return;
  }

  const data = window.quedamosBooking;
  if (!data) {
    return;
  }

  const price = parseFloat(data.coursePrice) || 0;
  const symbol = data.currencySymbol || '';

  const participantsRow = card.querySelector(PARTICIPANTS_ROW_SELECTOR);
  const subtotalRow = card.querySelector(SUBTOTAL_SELECTOR);
  const totalRow = card.querySelector(TOTAL_SELECTOR);

  input.addEventListener('input', function() {
    const participants = parseInt(input.value, 10) || 0;
    const total = participants * price;

    if (participantsRow) {
      participantsRow.textContent = `${participants} participants × ${formatAmount(price, symbol)}`;
    }

    if (subtotalRow) {
      subtotalRow.textContent = formatAmount(total, symbol);
    }

    if (totalRow) {
      totalRow.textContent = formatAmount(total, symbol);
    }
  });
}

document.addEventListener('DOMContentLoaded', initBookingSummary);
