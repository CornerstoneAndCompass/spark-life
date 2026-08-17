<?php
/**
 * CC Fields section template: CTA Band.
 *
 * Mirrors cta_band() in calltheplumberguy/build.py:
 * a fixed dark, centred closing call-to-action — <section class="ctaband">
 * with a .wrap.ctaband__in.reveal inner holding an .eyebrow.eyebrow--lime
 * "Get in touch", an .ctaband__title, an .ctaband__lead and an
 * .ctaband__actions row with a primary "Call {phone}" button (phone icon)
 * and a ghost-light "Book online" button (booking_url).
 *
 * Receives $section_data — keys are the field 'name's defined for the
 * 'cta_band' section type in includes/class-cc-sections.php.
 */
if (!defined('ABSPATH')) exit;

// ── Text fields ────────────────────────────────────────────────────────────
$title = isset($section_data['title']) ? $section_data['title'] : '';
$lead  = isset($section_data['lead'])  ? $section_data['lead']  : '';

// ── Buttons ────────────────────────────────────────────────────────────────
$primary_btn_text   = isset($section_data['primary_btn_text'])   ? $section_data['primary_btn_text']   : '';
$primary_btn_url    = isset($section_data['primary_btn_url'])    ? $section_data['primary_btn_url']    : '';
$secondary_btn_text = isset($section_data['secondary_btn_text']) ? $section_data['secondary_btn_text'] : '';
$secondary_btn_url  = isset($section_data['secondary_btn_url'])  ? $section_data['secondary_btn_url']  : '';

// ── Global contact vars ────────────────────────────────────────────────────
$company_phone = ccf_get_var('company_phone');
$company_tel   = ccf_get_var('company_tel');
$booking_url   = ccf_get_var('booking_url');
$tel_href      = 'tel:' . preg_replace('/[^0-9+]/', '', $company_tel);

// Default the primary CTA to a phone call when no explicit override is set.
if ($primary_btn_url === '' && $company_tel !== '') {
    $primary_btn_url = $tel_href;
}
if ($primary_btn_text === '' && $company_phone !== '') {
    $primary_btn_text = 'Call ' . $company_phone;
}

// Default the secondary CTA to "Book online" → booking_url.
if ($secondary_btn_url === '' && $booking_url !== '') {
    $secondary_btn_url = $booking_url;
}
if ($secondary_btn_text === '') {
    $secondary_btn_text = 'Book online';
}

// Only show the phone icon when the primary button is an actual tel: call.
$primary_is_tel = (strpos($primary_btn_url, 'tel:') === 0);

// Inline SVG icon (verbatim from build.py — ICON_PHONE_LG, 22×22).
$icon_phone_lg = '<svg viewBox="0 0 24 24" aria-hidden="true" width="22" height="22"><path fill="currentColor" d="M6.6 10.8a15.5 15.5 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.58 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.4 11.4 0 0 0 .58 3.6 1 1 0 0 1-.25 1Z"/></svg>';
?>
<section class="ctaband">
  <div class="wrap ctaband__in reveal">
    <p class="eyebrow eyebrow--lime">Get in touch</p>
    <?php if ($title !== '') : ?>
    <h2 class="ctaband__title"><?php echo esc_html($title); ?></h2>
    <?php endif; ?>
    <?php if ($lead !== '') : ?>
    <p class="ctaband__lead"><?php echo esc_html($lead); ?></p>
    <?php endif; ?>
    <?php if ($primary_btn_text !== '' || $secondary_btn_text !== '') : ?>
    <div class="ctaband__actions">
      <?php if ($primary_btn_text !== '' && $primary_btn_url !== '') : ?>
      <a class="btn btn--primary btn--lg" href="<?php echo esc_url($primary_btn_url); ?>"><?php if ($primary_is_tel) { echo $icon_phone_lg; } ?><?php echo esc_html($primary_btn_text); ?></a>
      <?php endif; ?>
      <?php if ($secondary_btn_text !== '' && $secondary_btn_url !== '') : ?>
      <a class="btn btn--ghost-light btn--lg" href="<?php echo esc_url($secondary_btn_url); ?>"><?php echo esc_html($secondary_btn_text); ?></a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
