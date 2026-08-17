<?php
if (!defined('ABSPATH')) exit;

/**
 * Section: Intro + Stats — two-column intro above an animated stats row.
 * Mirrors the home "Who we are" block (index.html) and the about-page intro
 * in build.py: .wrap.intro__in with .intro__lead (eyebrow + heading, optional
 * image + button) on the left and .intro__body (prose + optional textlink) on
 * the right, followed by a .wrap.stats.reveal grid of .stat tiles. Each .stat
 * has a .stat__num that either counts up (data-count + optional data-suffix,
 * visible text "0") or shows a plain value (data-plain="1"), plus a .stat__label.
 *
 * Fields: bg (paper|white|ink), eyebrow, heading, body, image, image_url,
 * button_text, button_url, stats (repeater of { number, suffix, label, plain }).
 */

$bg         = isset($section_data['bg']) ? $section_data['bg'] : 'paper';
$eyebrow    = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$heading    = isset($section_data['heading']) ? $section_data['heading'] : '';
$body       = isset($section_data['body']) ? $section_data['body'] : '';
$button_text = isset($section_data['button_text']) ? $section_data['button_text'] : '';
$button_url = isset($section_data['button_url']) ? $section_data['button_url'] : '';
$stats      = isset($section_data['stats']) && is_array($section_data['stats']) ? $section_data['stats'] : array();

// Restrict bg to the known variants; fall back to the default (paper).
if (!in_array($bg, array('paper', 'white', 'ink'), true)) {
    $bg = 'paper';
}

// Resolve the optional lead image: attachment id first, then *_url fallback.
$image_url = !empty($section_data['image'])
    ? Ccf_Renderer::get_image_url($section_data['image'])
    : (isset($section_data['image_url']) ? $section_data['image_url'] : '');

// Inline arrow icon — copied verbatim from ICON_ARROW in build.py.
$icon_arrow = '<svg viewBox="0 0 24 24" aria-hidden="true" width="16" height="16">'
    . '<path fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" '
    . 'stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>';
?>
<section class="section section--<?php echo esc_attr($bg); ?> reveal">
  <div class="wrap intro__in">
    <div class="intro__lead reveal">
      <?php if ($eyebrow !== '') : ?>
      <p class="eyebrow"><?php echo esc_html($eyebrow); ?></p>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="intro__heading"><?php echo esc_html($heading); ?></h2>
      <?php endif; ?>
      <?php if ($image_url !== '') : ?>
      <figure class="about-portrait">
        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($heading); ?>">
      </figure>
      <?php endif; ?>
    </div>
    <div class="intro__body reveal">
      <?php echo wp_kses_post(wpautop($body)); ?>
      <?php if ($button_text !== '' && $button_url !== '') : ?>
      <a class="textlink" href="<?php echo esc_url($button_url); ?>"><?php echo esc_html($button_text) . ' ' . $icon_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — static inline SVG icon ?></a>
      <?php endif; ?>
    </div>
  </div>
  <?php if (!empty($stats)) : ?>
  <div class="wrap stats reveal">
    <?php foreach ($stats as $stat) :
        if (!is_array($stat)) continue;
        $number = isset($stat['number']) ? trim($stat['number']) : '';
        $suffix = isset($stat['suffix']) ? $stat['suffix'] : '';
        $label  = isset($stat['label']) ? $stat['label'] : '';
        $plain  = !empty($stat['plain']) && $stat['plain'] !== '0';
        if ($number === '' && $label === '') continue;
    ?>
    <div class="stat"><span class="stat__num"<?php
        if ($plain) {
            echo ' data-plain="1"';
        } else {
            echo ' data-count="' . esc_attr($number) . '"';
            if ($suffix !== '') {
                echo ' data-suffix="' . esc_attr($suffix) . '"';
            }
        }
    ?>><?php echo $plain ? esc_html($number) : '0'; ?></span><span class="stat__label"><?php echo esc_html($label); ?></span></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
