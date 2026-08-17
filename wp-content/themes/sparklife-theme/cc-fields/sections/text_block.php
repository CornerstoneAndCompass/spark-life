<?php
/**
 * Section: text_block — rich-text prose with an optional heading.
 */
if (!defined('ABSPATH')) exit;

$bg        = sl_field($section_data, 'bg', 'white');
$kicker    = sl_field($section_data, 'kicker');
$heading   = sl_field($section_data, 'heading');
$highlight = sl_field($section_data, 'heading_highlight');
$content   = sl_field($section_data, 'content');
$width     = sl_field($section_data, 'max_width', 'normal');
$is_ink    = ($bg === 'ink' || $bg === 'blue');

if ($content === '' && $heading === '') return;
?>
<section class="section section--<?php echo esc_attr($bg); ?>">
  <div class="wrap">
    <div class="prose prose--<?php echo esc_attr($width); ?>">
      <?php if ($kicker !== '') : ?>
      <span class="kicker<?php echo $is_ink ? ' kicker--light' : ''; ?>"><?php echo esc_html($kicker); ?></span>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="section__title<?php echo $is_ink ? ' section__title--light' : ''; ?>"><?php echo wp_kses_post(sl_highlight($heading, $highlight)); ?></h2>
      <?php endif; ?>
      <?php echo wp_kses_post(wpautop($content)); ?>
    </div>
  </div>
</section>
