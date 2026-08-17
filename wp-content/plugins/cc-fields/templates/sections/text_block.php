<?php
if (!defined('ABSPATH')) exit;

/**
 * Section: text_block — generic rich-text prose block (covid-19 page).
 * Fields (from class-cc-sections.php): bg, eyebrow, heading, content, max_width.
 * Markup mirrors build.py / covid-19.html: <section class="section section--<bg>">
 * with an optional .section__head (eyebrow + section-title) followed by
 * <div class="prose reveal"> rendering content through wp_kses_post().
 * max_width controls prose width: narrow → .measure, wide → centred unconstrained,
 * normal → default .prose width (centred).
 */

$bg        = isset($section_data['bg']) ? $section_data['bg'] : 'paper';
$eyebrow   = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$heading   = isset($section_data['heading']) ? $section_data['heading'] : '';
$content   = isset($section_data['content']) ? $section_data['content'] : '';
$max_width = isset($section_data['max_width']) ? $section_data['max_width'] : 'normal';

$is_dark = ($bg === 'ink');

// On the ink (dark) background, heading + eyebrow + prose flip to light variants.
$title_cls   = 'section-title' . ($is_dark ? ' section-title--light' : '');
$eyebrow_cls = 'eyebrow' . ($is_dark ? ' eyebrow--lime' : '');
$prose_cls   = 'prose reveal' . ($is_dark ? ' prose--light' : '') . ($max_width === 'narrow' ? ' measure' : '');
?>
<section class="section section--<?php echo esc_attr($bg); ?> reveal">
  <div class="wrap">
    <?php if ($eyebrow !== '' || $heading !== '') : ?>
    <div class="section__head reveal">
      <?php if ($eyebrow !== '') : ?>
      <p class="<?php echo esc_attr($eyebrow_cls); ?>"><?php echo esc_html($eyebrow); ?></p>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="<?php echo esc_attr($title_cls); ?>"><?php echo esc_html($heading); ?></h2>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="<?php echo esc_attr($prose_cls); ?>" style="margin:0 auto">
      <?php echo wp_kses_post($content); ?>
    </div>
  </div>
</section>
