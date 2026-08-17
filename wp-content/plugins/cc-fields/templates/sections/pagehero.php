<?php
/**
 * CC Fields section template: Page Hero (dark page header).
 *
 * Mirrors pagehero() in calltheplumberguy/build.py so the theme's
 * assets/css/main.css styles it identically:
 *   .pagehero > [.pagehero__bg img] + .pagehero__glow
 *     + .wrap.pagehero__in > .breadcrumb + .eyebrow--lime
 *       + h1.pagehero__title + .pagehero__lead + [.pagehero__actions]
 *
 * Breadcrumb is built from WordPress: Home / <current page title>.
 * Receives $section_data (keys = field 'name's for the 'pagehero' section).
 */
if (!defined('ABSPATH')) exit;

$eyebrow         = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$title           = isset($section_data['title']) ? $section_data['title'] : '';
$title_highlight = isset($section_data['title_highlight']) ? $section_data['title_highlight'] : '';
$lead            = isset($section_data['lead']) ? $section_data['lead'] : '';
$show_actions    = isset($section_data['show_actions']) ? $section_data['show_actions'] : '';

$primary_btn_text   = isset($section_data['primary_btn_text']) ? $section_data['primary_btn_text'] : '';
$primary_btn_url    = isset($section_data['primary_btn_url']) ? $section_data['primary_btn_url'] : '';
$secondary_btn_text = isset($section_data['secondary_btn_text']) ? $section_data['secondary_btn_text'] : '';
$secondary_btn_url  = isset($section_data['secondary_btn_url']) ? $section_data['secondary_btn_url'] : '';

// Background image: resolve attachment id first, then *_url fallback.
$bg_url = !empty($section_data['bg_image'])
    ? Ccf_Renderer::get_image_url($section_data['bg_image'])
    : (isset($section_data['bg_image_url']) ? $section_data['bg_image_url'] : '');

// Global contact details / booking link.
$company_phone = ccf_get_var('company_phone');
$company_tel   = preg_replace('/[^0-9+]/', '', ccf_get_var('company_tel'));
$tel_href      = 'tel:' . $company_tel;
$booking_url   = ccf_get_var('booking_url');

// Buttons default to the global call + booking CTAs when no per-section text is set.
if ($primary_btn_text === '') { $primary_btn_text = $company_phone !== '' ? 'Call ' . $company_phone : 'Call now'; }
if ($primary_btn_url === '')  { $primary_btn_url = $tel_href; }
if ($secondary_btn_text === '') { $secondary_btn_text = 'Book online'; }
if ($secondary_btn_url === '')  { $secondary_btn_url = $booking_url !== '' ? $booking_url : '#'; }

// Title with optional lime-highlighted word (matches the static site's .u-lime span).
$title_html = esc_html($title);
if ($title_highlight !== '' && $title !== '' && stripos($title, $title_highlight) !== false) {
    $pos = stripos($title, $title_highlight);
    $title_html = esc_html(substr($title, 0, $pos))
        . '<span class="u-lime">' . esc_html(substr($title, $pos, strlen($title_highlight))) . '</span>'
        . esc_html(substr($title, $pos + strlen($title_highlight)));
} elseif ($title_highlight !== '') {
    $title_html = esc_html($title) . ' <span class="u-lime">' . esc_html($title_highlight) . '</span>';
}

// Inline SVG icons copied verbatim from build.py (phone large, arrow).
$icon_phone_lg = '<svg viewBox="0 0 24 24" aria-hidden="true" width="22" height="22">'
  . '<path fill="currentColor" d="M6.6 10.8a15.5 15.5 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 '
  . '11.4 11.4 0 0 0 3.6.58 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 '
  . '11.4 11.4 0 0 0 .58 3.6 1 1 0 0 1-.25 1Z"/></svg>';
?>
<section class="pagehero">
  <?php if ($bg_url !== '') : ?>
  <div class="pagehero__bg"><img src="<?php echo esc_url($bg_url); ?>" alt=""></div>
  <?php endif; ?>
  <div class="pagehero__glow" aria-hidden="true"></div>
  <div class="wrap pagehero__in">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
      <span aria-hidden="true">/</span>
      <span><?php echo esc_html(get_the_title()); ?></span>
    </nav>
    <?php if ($eyebrow !== '') : ?>
    <p class="eyebrow eyebrow--lime"><?php echo esc_html($eyebrow); ?></p>
    <?php endif; ?>
    <h1 class="pagehero__title"><?php echo wp_kses_post($title_html); ?></h1>
    <?php if ($lead !== '') : ?>
    <p class="pagehero__lead"><?php echo esc_html($lead); ?></p>
    <?php endif; ?>
    <?php if (!empty($show_actions)) : ?>
    <div class="pagehero__actions">
      <a class="btn btn--primary btn--lg" href="<?php echo esc_url($primary_btn_url); ?>"><?php echo $icon_phone_lg; ?><?php echo esc_html($primary_btn_text); ?></a>
      <a class="btn btn--ghost-light btn--lg" href="<?php echo esc_url($secondary_btn_url); ?>"><?php echo esc_html($secondary_btn_text); ?></a>
    </div>
    <?php endif; ?>
  </div>
</section>
