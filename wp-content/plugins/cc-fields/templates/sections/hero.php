<?php
/**
 * CC Fields section template: Home Hero.
 *
 * Mirrors the build_home() hero block in calltheplumberguy/build.py:
 * full-bleed .hero with a .hero__bg image, .hero__content (eyebrow, .hero__title
 * with a lime highlight, .hero__lead, two .hero__actions buttons, .hero__trust)
 * and a right-hand .leadform card that posts to CC Fields.
 *
 * Receives $section_data — keys are the field 'name's defined for the 'hero'
 * section type in includes/class-cc-sections.php.
 */
if (!defined('ABSPATH')) exit;

// ── Text fields ────────────────────────────────────────────────────────────
$eyebrow            = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$title              = isset($section_data['title']) ? $section_data['title'] : '';
$title_highlight    = isset($section_data['title_highlight']) ? $section_data['title_highlight'] : '';
$lead               = isset($section_data['lead']) ? $section_data['lead'] : '';
$trust_text         = isset($section_data['trust_text']) ? $section_data['trust_text'] : '';

// ── Buttons ────────────────────────────────────────────────────────────────
$primary_btn_text   = isset($section_data['primary_btn_text']) ? $section_data['primary_btn_text'] : '';
$primary_btn_url    = isset($section_data['primary_btn_url']) ? $section_data['primary_btn_url'] : '';
$secondary_btn_text = isset($section_data['secondary_btn_text']) ? $section_data['secondary_btn_text'] : '';
$secondary_btn_url  = isset($section_data['secondary_btn_url']) ? $section_data['secondary_btn_url'] : '';

// ── Lead form ──────────────────────────────────────────────────────────────
$show_form          = isset($section_data['show_form']) ? $section_data['show_form'] : '1';
$form_title         = isset($section_data['form_title']) ? $section_data['form_title'] : '';
$form_note          = isset($section_data['form_note']) ? $section_data['form_note'] : '';
$form_button_text   = isset($section_data['form_button_text']) ? $section_data['form_button_text'] : '';

// ── Background image: attachment id first, then *_url fallback ──────────────
$bg_image = !empty($section_data['bg_image'])
    ? Ccf_Renderer::get_image_url($section_data['bg_image'])
    : (isset($section_data['bg_image_url']) ? $section_data['bg_image_url'] : '');

// ── Global contact vars ────────────────────────────────────────────────────
$company_phone = ccf_get_var('company_phone');
$company_tel   = ccf_get_var('company_tel');
$tel_href      = 'tel:' . preg_replace('/[^0-9+]/', '', $company_tel);

// Default the primary CTA to a phone call when no explicit URL is set.
if ($primary_btn_url === '' && $company_tel !== '') {
    $primary_btn_url = $tel_href;
}
if ($primary_btn_text === '' && $company_phone !== '') {
    $primary_btn_text = 'Call ' . $company_phone;
}

// Inline SVG icons (verbatim from build.py).
$icon_phone_lg = '<svg viewBox="0 0 24 24" aria-hidden="true" width="22" height="22"><path fill="currentColor" d="M6.6 10.8a15.5 15.5 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.58 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.4 11.4 0 0 0 .58 3.6 1 1 0 0 1-.25 1Z"/></svg>';

// Build the lime-highlighted title. If a highlight word is provided and appears
// in the title, wrap that occurrence; otherwise append it as a trailing span.
$title_html = esc_html($title);
if ($title_highlight !== '') {
    $needle = esc_html($title_highlight);
    $span   = '<span class="u-lime">' . $needle . '</span>';
    if (strpos($title_html, $needle) !== false) {
        $title_html = preg_replace('/' . preg_quote($needle, '/') . '/', $span, $title_html, 1);
    } else {
        $title_html = trim($title_html . ' ' . $span);
    }
}
?>
<section class="hero" id="home">
  <?php if ($bg_image !== '') : ?>
  <div class="hero__bg" aria-hidden="true"><img src="<?php echo esc_url($bg_image); ?>" alt=""></div>
  <?php endif; ?>
  <div class="wrap hero__in">
    <div class="hero__content reveal">
      <?php if ($eyebrow !== '') : ?>
      <p class="eyebrow eyebrow--lime"><?php echo esc_html($eyebrow); ?></p>
      <?php endif; ?>
      <?php if ($title_html !== '') : ?>
      <h1 class="hero__title"><?php echo wp_kses_post($title_html); ?></h1>
      <?php endif; ?>
      <?php if ($lead !== '') : ?>
      <p class="hero__lead"><?php echo esc_html($lead); ?></p>
      <?php endif; ?>
      <?php if ($primary_btn_text !== '' || $secondary_btn_text !== '') : ?>
      <div class="hero__actions">
        <?php if ($primary_btn_text !== '') : ?>
        <a class="btn btn--primary btn--lg" href="<?php echo esc_url($primary_btn_url); ?>"><?php echo $icon_phone_lg; ?><?php echo esc_html($primary_btn_text); ?></a>
        <?php endif; ?>
        <?php if ($secondary_btn_text !== '') : ?>
        <a class="btn btn--ghost-light btn--lg" href="<?php echo esc_url($secondary_btn_url); ?>"><?php echo esc_html($secondary_btn_text); ?></a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if ($trust_text !== '') : ?>
      <ul class="hero__trust">
        <li class="hero__trust-stars"><span class="stars" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span> <?php echo esc_html($trust_text); ?></li>
      </ul>
      <?php endif; ?>
    </div>
    <?php if ($show_form === '1' || $show_form === 1 || $show_form === true) : ?>
    <div class="leadform reveal">
      <form class="js-form" id="heroForm" method="post" action="" novalidate>
        <input type="hidden" name="action" value="ccf_submit_form">
        <input type="hidden" name="page_id" value="<?php echo esc_attr(get_the_ID()); ?>">
        <input type="hidden" name="ccf_form_nonce" value="<?php echo esc_attr(wp_create_nonce('ccf_form_submit')); ?>">
        <input type="text" name="ccf_hp" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">
        <div class="leadform__head">
          <h2><?php echo esc_html($form_title !== '' ? $form_title : 'Request a callback'); ?></h2>
          <?php if ($form_note !== '') : ?>
          <p><?php echo esc_html($form_note); ?></p>
          <?php endif; ?>
        </div>
        <div class="leadform__row">
          <label class="field"><span>Your name</span><input type="text" name="name" autocomplete="name" required></label>
          <label class="field"><span>Phone</span><input type="tel" name="phone" autocomplete="tel" required></label>
        </div>
        <label class="field"><span>Suburb</span><input type="text" name="suburb" autocomplete="address-level2"></label>
        <label class="field"><span>I need help with</span>
          <select name="service">
            <option>Hot Water Systems</option>
            <option>Blocked Drains &amp; CCTV</option>
            <option>Gas Services</option>
            <option>Emergency Plumbing</option>
            <option>NDIS &amp; Home Modifications</option>
            <option>General Plumbing</option>
            <option>Plumbing Inspections</option>
            <option>Heating &amp; Cooling</option>
            <option>Something else</option>
          </select>
        </label>
        <button type="submit" class="btn btn--primary btn--block btn--lg"><?php echo esc_html($form_button_text !== '' ? $form_button_text : 'Get my callback'); ?></button>
        <?php if ($company_phone !== '') : ?>
        <p class="leadform__foot">Or call us now on <a href="<?php echo esc_attr($tel_href); ?>"><?php echo esc_html($company_phone); ?></a></p>
        <?php endif; ?>
        <p class="leadform__success cform__success" hidden>Thanks, we've got it. We'll call you back as soon as we can.<?php echo $company_phone !== '' ? ' Need us urgently? Call <a href="' . esc_attr($tel_href) . '">' . esc_html($company_phone) . '</a>.' : ''; ?></p>
      </form>
    </div>
    <?php endif; ?>
  </div>
</section>
