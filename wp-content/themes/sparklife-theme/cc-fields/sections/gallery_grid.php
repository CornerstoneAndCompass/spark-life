<?php
/**
 * Section: gallery_grid — job photos, with optional tall / wide tiles.
 */
if (!defined('ABSPATH')) exit;

$bg        = sl_field($section_data, 'bg', 'white');
$kicker    = sl_field($section_data, 'kicker');
$heading   = sl_field($section_data, 'heading');
$highlight = sl_field($section_data, 'heading_highlight');
$intro     = sl_field($section_data, 'intro');
$is_ink    = ($bg === 'ink' || $bg === 'blue');

$images = (isset($section_data['images']) && is_array($section_data['images'])) ? $section_data['images'] : array();
if (!$images) return;
?>
<section class="section section--<?php echo esc_attr($bg); ?>">
  <div class="wrap">
    <?php if ($kicker !== '' || $heading !== '' || $intro !== '') : ?>
    <div class="section__head">
      <?php if ($kicker !== '') : ?>
      <span class="kicker<?php echo $is_ink ? ' kicker--light' : ''; ?>"><?php echo esc_html($kicker); ?></span>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="section__title<?php echo $is_ink ? ' section__title--light' : ''; ?>"><?php echo wp_kses_post(sl_highlight($heading, $highlight)); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?><p class="section__sub"><?php echo esc_html($intro); ?></p><?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="gallery">
      <?php foreach ($images as $img) :
        $src = sl_image($img, 'image');
        if (!$src) continue;
        $alt  = isset($img['alt']) ? $img['alt'] : '';
        $size = isset($img['size']) && in_array($img['size'], array('tall', 'wide'), true) ? $img['size'] : 'normal';
      ?>
      <figure class="gallery__item gallery__item--<?php echo esc_attr($size); ?>">
        <img src="<?php echo esc_url($src); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy">
        <?php if ($alt !== '') : ?><figcaption><?php echo esc_html($alt); ?></figcaption><?php endif; ?>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
