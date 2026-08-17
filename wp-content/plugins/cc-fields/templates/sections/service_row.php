<?php
if (!defined('ABSPATH')) exit;

/**
 * Section: Service Row (alt image / text)
 * Mirrors the .srow block from build.py (build_service): an image beside an
 * eyebrow + optional number + heading + prose, with an optional button.
 * Toggle image_right adds .srow--rev so the media moves to the right.
 */

$bg = isset($section_data['bg']) ? $section_data['bg'] : 'paper';
if (!in_array($bg, array('paper', 'white', 'ink'), true)) { $bg = 'paper'; }

$eyebrow = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$number  = isset($section_data['number']) ? $section_data['number'] : '';
$heading = isset($section_data['heading']) ? $section_data['heading'] : '';
$body    = isset($section_data['body']) ? $section_data['body'] : '';

$image_url = !empty($section_data['image'])
    ? Ccf_Renderer::get_image_url($section_data['image'])
    : (isset($section_data['image_url']) ? $section_data['image_url'] : '');

$image_right = !empty($section_data['image_right']);

$button_text = isset($section_data['button_text']) ? $section_data['button_text'] : '';
$button_url  = isset($section_data['button_url']) ? $section_data['button_url'] : '';
?>
<section class="section section--<?php echo esc_attr($bg); ?> reveal">
  <div class="wrap">
    <div class="srow<?php echo $image_right ? ' srow--rev' : ''; ?>">
      <div class="srow__media reveal">
        <?php if ($image_url !== '') : ?>
        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($heading); ?>">
        <?php endif; ?>
      </div>
      <div class="srow__text reveal">
        <?php if ($eyebrow !== '') : ?>
        <p class="eyebrow"><?php echo esc_html($eyebrow); ?></p>
        <?php endif; ?>
        <?php if ($number !== '') : ?>
        <span class="srow__num"><?php echo esc_html($number); ?></span>
        <?php endif; ?>
        <?php if ($heading !== '') : ?>
        <h2 class="srow__title"><?php echo esc_html($heading); ?></h2>
        <?php endif; ?>
        <?php echo wp_kses_post($body); ?>
        <?php if ($button_text !== '' && $button_url !== '') : ?>
        <a class="btn btn--primary" href="<?php echo esc_url($button_url); ?>"><?php echo esc_html($button_text); ?></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
