<?php
if (!defined('ABSPATH')) exit;

/**
 * Services Index — numbered, linked service rows in a 2-column grid.
 * Mirrors build.py svc-index (.svc-index > .svc-row link rows) + section head.
 */

$bg      = isset($section_data['bg']) ? $section_data['bg'] : 'white';
$eyebrow = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$heading = isset($section_data['heading']) ? $section_data['heading'] : '';
$intro   = isset($section_data['intro']) ? $section_data['intro'] : '';
$services = (isset($section_data['services']) && is_array($section_data['services'])) ? $section_data['services'] : array();

// Inline arrow icon — verbatim from build.py (ICON_ARROW).
$icon_arrow = '<svg viewBox="0 0 24 24" aria-hidden="true" width="16" height="16">'
  . '<path fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" '
  . 'stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>';
?>
<section class="section section--<?php echo esc_attr($bg); ?> reveal">
  <div class="wrap">
    <div class="section__head reveal">
      <?php if ($eyebrow !== '') : ?>
      <p class="eyebrow"><?php echo esc_html($eyebrow); ?></p>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="section-title"><?php echo esc_html($heading); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?>
      <p class="section-intro"><?php echo esc_html($intro); ?></p>
      <?php endif; ?>
    </div>
    <div class="svc-index">
      <?php foreach ($services as $i => $s) :
        $name = isset($s['name']) ? $s['name'] : '';
        $desc = isset($s['desc']) ? $s['desc'] : '';
        $url  = isset($s['url']) ? $s['url'] : '';
        $num  = sprintf('%02d', $i + 1);
      ?>
      <a class="svc-row reveal" href="<?php echo esc_url($url); ?>">
        <span class="svc-row__num"><?php echo esc_html($num); ?></span>
        <span class="svc-row__name"><?php echo esc_html($name); ?></span>
        <span class="svc-row__desc"><?php echo esc_html($desc); ?></span>
        <span class="svc-row__arrow" aria-hidden="true"><?php echo $icon_arrow; ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
