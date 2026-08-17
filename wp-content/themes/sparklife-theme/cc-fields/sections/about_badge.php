<?php
/**
 * Section: about_badge — the rotating dashed badge (logo or photo) beside a
 * copy block. Ports the .about block from the static build.
 */
if (!defined('ABSPATH')) exit;

$bg        = sl_field($section_data, 'bg', 'white');
$kicker    = sl_field($section_data, 'kicker');
$heading   = sl_field($section_data, 'heading');
$highlight = sl_field($section_data, 'heading_highlight');
$body      = sl_field($section_data, 'body');
$sticker   = sl_field($section_data, 'sticker');
$is_ink    = ($bg === 'ink' || $bg === 'blue');

$image = sl_image($section_data, 'image');
$is_photo = ($image !== '');
if (!$is_photo) $image = SL_URL . '/assets/img/logo.png';

$image_right = sl_on(sl_field($section_data, 'image_right', '0'));
$btn_text = sl_field($section_data, 'button_text');
$btn_url  = sl_link(sl_field($section_data, 'button_url'));
?>
<section class="section section--<?php echo esc_attr($bg); ?> about">
  <div class="wrap about__grid<?php echo $image_right ? ' about__grid--flip' : ''; ?>">
    <div class="about__media">
      <div class="about__badge<?php echo $is_photo ? ' about__badge--photo' : ''; ?>">
        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($is_photo ? $heading : get_bloginfo('name')); ?>" width="160" height="160" loading="lazy">
      </div>
      <?php if ($sticker !== '') : ?>
      <span class="about__sticker"><?php echo esc_html($sticker); ?></span>
      <?php endif; ?>
    </div>
    <div class="about__copy">
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
      <a class="btn btn--dark" href="<?php echo esc_url($btn_url); ?>"><?php echo esc_html($btn_text); ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>
