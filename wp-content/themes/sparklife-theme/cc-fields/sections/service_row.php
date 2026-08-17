<?php
/**
 * Section: service_row — image beside heading + prose, sides alternating.
 */
if (!defined('ABSPATH')) exit;

$bg        = sl_field($section_data, 'bg', 'white');
$kicker    = sl_field($section_data, 'kicker');
$number    = sl_field($section_data, 'number');
$heading   = sl_field($section_data, 'heading');
$highlight = sl_field($section_data, 'heading_highlight');
$body      = sl_field($section_data, 'body');
$is_ink    = ($bg === 'ink' || $bg === 'blue');

$image = sl_image($section_data, 'image');

$image_right = sl_on(sl_field($section_data, 'image_right', '0'));
$btn_text = sl_field($section_data, 'button_text');
$btn_url  = sl_link(sl_field($section_data, 'button_url'));
?>
<section class="section section--<?php echo esc_attr($bg); ?> svcrow">
  <div class="wrap svcrow__grid<?php echo $image_right ? ' svcrow__grid--flip' : ''; ?><?php echo $image ? '' : ' svcrow__grid--noimage'; ?>">
    <?php if ($image) : ?>
    <div class="svcrow__media">
      <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($heading); ?>" loading="lazy">
      <?php if ($number !== '') : ?><span class="svcrow__num"><?php echo esc_html($number); ?></span><?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="svcrow__copy">
      <?php if ($kicker !== '') : ?>
      <span class="kicker<?php echo $is_ink ? ' kicker--light' : ''; ?>"><?php echo esc_html($kicker); ?></span>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="section__title<?php echo $is_ink ? ' section__title--light' : ''; ?>"><?php echo wp_kses_post(sl_highlight($heading, $highlight)); ?></h2>
      <?php endif; ?>
      <?php if ($body !== '') : ?>
      <div class="prose"><?php echo wp_kses_post(wpautop($body)); ?></div>
      <?php endif; ?>
      <?php if ($btn_text !== '' && $btn_url !== '') : ?>
      <a class="btn btn--primary" href="<?php echo esc_url($btn_url); ?>"><?php echo esc_html($btn_text); ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>
