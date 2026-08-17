<?php
/**
 * CC Fields section template: Emergency Band (lime call-out strip).
 *
 * Mirrors emergency_band() in calltheplumberguy/build.py so the theme's
 * assets/css/main.css styles it identically:
 *   <section class="emergency">
 *     > .wrap.emergency__in.reveal
 *         > .emergency__text
 *             ( .eyebrow.eyebrow--dark + h2.emergency__title + p.emergency__lead )
 *         + a.btn.btn--dark.btn--xl[href=tel:]
 *             ( ICON_PHONE_LG + <span><small>label</small><strong>phone</strong></span> )
 *
 * Fixed section (keeps its own .emergency wrapper from build.py — no bg variant).
 * Receives $section_data (keys = field 'name's for the 'emergency_band' section).
 */
if (!defined('ABSPATH')) exit;

// Per-section copy overrides (defaults below mirror build.py's static strings).
$title       = isset($section_data['title']) ? $section_data['title'] : '';
$lead        = isset($section_data['lead']) ? $section_data['lead'] : '';
$button_text = isset($section_data['button_text']) ? $section_data['button_text'] : '';
$button_url  = isset($section_data['button_url']) ? $section_data['button_url'] : '';

// Global contact details.
$company_phone = ccf_get_var('company_phone');
$company_tel   = preg_replace('/[^0-9+]/', '', ccf_get_var('company_tel'));
$tel_href      = 'tel:' . $company_tel;

// Defaults taken verbatim from emergency_band() in build.py.
if ($title === '') { $title = "Burst pipe, gas leak or no hot water? Don't wait it out."; }
if ($lead === '')  { $lead  = "A leak left running does real damage fast. The sooner you call, the less you'll be dealing with."; }

// Button: small label over the strong phone number; URL defaults to tel:phone.
if ($button_text === '') { $button_text = 'Call us now'; }
if ($button_url === '')  { $button_url = $tel_href; }

// Phone shown in the button defaults to the global company phone.
$button_phone = $company_phone !== '' ? $company_phone : $button_text;

// Inline SVG icon copied verbatim from build.py (ICON_PHONE_LG = 22x22 phone).
$icon_phone_lg = '<svg viewBox="0 0 24 24" aria-hidden="true" width="22" height="22">'
  . '<path fill="currentColor" d="M6.6 10.8a15.5 15.5 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 '
  . '11.4 11.4 0 0 0 3.6.58 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 '
  . '11.4 11.4 0 0 0 .58 3.6 1 1 0 0 1-.25 1Z"/></svg>';
?>
<section class="emergency">
  <div class="wrap emergency__in reveal">
    <div class="emergency__text">
      <p class="eyebrow eyebrow--dark">Plumbing emergency?</p>
      <h2 class="emergency__title"><?php echo esc_html($title); ?></h2>
      <p class="emergency__lead"><?php echo esc_html($lead); ?></p>
    </div>
    <a class="btn btn--dark btn--xl" href="<?php echo esc_url($button_url); ?>"><?php echo $icon_phone_lg; ?>
      <span><small><?php echo esc_html($button_text); ?></small><strong><?php echo esc_html($button_phone); ?></strong></span></a>
  </div>
</section>
