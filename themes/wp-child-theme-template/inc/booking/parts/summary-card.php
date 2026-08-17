<?php
/**
 * Booking summary card view.
 *
 * Rendered by quedamos_booking_summary_shortcode(), which provides $course,
 * $course_price, $start_date, $location, $participants and $subtotal. Derived
 * values come from the module's helpers so this file stays readable as markup.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

$formatted_start_date = quedamos_format_course_date( $start_date );
$location_label       = quedamos_booking_location_label( $location );
?>

<div class="booking-summary-card">
  <div class="booking-details">
    <h2>Booking Details</h2>
    <div class="course-title"><span>Course:</span> <?php echo esc_html( $course ); ?></div>
    <div class="start-date"><span>Starts:</span> <?php echo esc_html( $formatted_start_date ); ?></div>
    <div class="sessions"><span>Block of 5 Classes</span></div>
    <div class="location">
      <span>Location:</span>
      <?php echo esc_html( $location_label ); ?>
    </div>

  </div>
  <hr>
  <div class="payment-details">
    <h3>Payment Details</h3>
    <div class="subtotal-row">
      <span>Subtotal</span>
      <span>£<?php echo esc_html( number_format( $subtotal, 0 ) ); ?></span>
    </div>
    <div class="participants"><?php echo esc_html( $participants ); ?> participants ×
      £<?php echo esc_html( number_format( $course_price, 0 ) ); ?></div>
  </div>
  <hr>
  <div class="total-row">
    <span>Total</span>
    <span>£<?php echo esc_html( number_format( $subtotal, 0 ) ); ?></span>
  </div>
</div>

<style>
button.wpforms-submit {
  width: 100% !important;
  padding: 8px !important;
  background-color: var(--wp--preset--color--custom-primary) !important;
  color: #fff !important;
  border-radius: 6px !important;
  letter-spacing: 0.5px !important;
}

.booking-form-container .wp-block-columns {
  @media (max-width: 782px) {
    .wp-block-column:nth-child(1) {
      order: 2;
    }

    .wp-block-column:nth-child(2) {
      order: 1;
    }
  }
}

.booking-summary-card {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
  padding: 32px 28px;
  max-width: 420px;
  margin: 40px auto;
  color: #222;
  border: 1px solid #e5e7eb;
}

.booking-details h2 {
  font-size: 2.2rem;
  color: #0a3d62;
  font-weight: 700;
  margin-bottom: 18px;
  letter-spacing: -1px;
}

.course-title {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 6px;
}

.start-date,
.location,
.sessions {
  font-size: 1.1rem;
  color: #555;
  margin-bottom: 4px;
}

.sessions {
  margin-bottom: 12px;
}

.payment-details h3 {
  font-size: 1.3rem;
  color: #0a3d62;
  font-weight: 700;
  margin-bottom: 10px;
  margin-top: 24px;
}

.subtotal-row,
.total-row {
  display: flex;
  justify-content: space-between;
  font-size: 1.15rem;
  margin-bottom: 6px;
}

.total-row {
  font-size: 1.3rem;
  font-weight: 700;
  margin-top: 18px;
  color: #0a3d62;
}

.participants {
  color: #888;
  font-size: 1.05rem;
  margin-bottom: 4px;
}

.book-now-btn {
  width: 100%;
  background: linear-gradient(90deg, #0a3d62 0%, #12a8e6 100%);
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 1.2rem;
  padding: 14px 0;
  margin-top: 28px;
  cursor: pointer;
  font-weight: 600;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 8px rgba(10, 61, 98, 0.08);
  transition: background 0.2s, box-shadow 0.2s;
}

.book-now-btn:hover {
  background: linear-gradient(90deg, #12a8e6 0%, #0a3d62 100%);
  box-shadow: 0 4px 16px rgba(10, 61, 98, 0.12);
}

hr {
  border: none;
  border-top: 1px solid #e0e0e0;
  margin: 28px 0;
}

@media (max-width: 600px) {
  .booking-summary-card {
    max-width: 100vw;
    min-height: 100vh;
    border-radius: 0;
    margin: 0;
    box-shadow: none;
    padding: 32px 8px;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Find the participants input inside the .qd-participants container
  const participantsInput = document.querySelector('.qd-participants input[type="number"]');
  if (!participantsInput) return;

  // Find the price per participant from the payment details row
  const priceText = document.querySelector('.participants');
  let pricePerParticipant = 0;
  if (priceText) {
    // Extract the price from the text, e.g., "2 participants × £150"
    const match = priceText.textContent.match(/£(\d+)/);
    if (match) pricePerParticipant = parseInt(match[1], 10);
  }

  // Elements to update
  const participantsRow = document.querySelector('.participants');
  const subtotalRow = document.querySelector('.subtotal-row span:last-child');
  const totalRow = document.querySelector('.total-row span:last-child');

  function updateBookingSummary() {
    const numParticipants = parseInt(participantsInput.value, 10) || 0;
    // Update participants text
    if (participantsRow) {
      participantsRow.textContent = `${numParticipants} participants × £${pricePerParticipant}`;
    }
    // Update subtotal and total
    const total = numParticipants * pricePerParticipant;
    if (subtotalRow) subtotalRow.textContent = `£${total}`;
    if (totalRow) totalRow.textContent = `£${total}`;
  }

  // Listen for input changes
  participantsInput.addEventListener('input', updateBookingSummary);
});
</script>