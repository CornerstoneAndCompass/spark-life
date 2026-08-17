<?php
if (!defined('ABSPATH')) exit;

/**
 * Section: Gallery Grid — section header above a masonry-style gallery.
 * Mirrors the gallery page in build.py / gallery.html: a .section__head.reveal
 * (eyebrow + heading + optional intro) followed by a .gallery.reveal grid of
 * <figure class="gallery__item"> tiles. Each tile takes an optional size
 * modifier (--tall / --wide), an <img loading="lazy"> and an optional
 * <figcaption> caption.
 *
 * Fields: bg (paper|white|ink, default white), eyebrow, heading, intro,
 * images (repeater of { image, image_url, alt, size }).
 */

$bg      = isset($section_data['bg']) ? $section_data['bg'] : 'white';
$eyebrow = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$heading = isset($section_data['heading']) ? $section_data['heading'] : '';
$intro   = isset($section_data['intro']) ? $section_data['intro'] : '';
$images  = isset($section_data['images']) && is_array($section_data['images']) ? $section_data['images'] : array();

// Restrict bg to the known variants; fall back to the default (white).
if (!in_array($bg, array('paper', 'white', 'ink'), true)) {
    $bg = 'white';
}
?>
<section class="section section--<?php echo esc_attr($bg); ?> reveal">
  <div class="wrap">
    <?php if ($eyebrow !== '' || $heading !== '' || $intro !== '') : ?>
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
    <?php endif; ?>
    <?php if (!empty($images)) : ?>
    <div class="gallery reveal">
      <?php foreach ($images as $img) :
          if (!is_array($img)) continue;

          // Resolve the image: attachment id first, then *_url fallback.
          $img_src = !empty($img['image'])
              ? Ccf_Renderer::get_image_url($img['image'])
              : (isset($img['image_url']) ? $img['image_url'] : '');
          if ($img_src === '') continue;

          $alt  = isset($img['alt']) ? $img['alt'] : '';
          $size = isset($img['size']) ? $img['size'] : '';

          $cls = 'gallery__item';
          if ($size === 'tall') {
              $cls .= ' gallery__item--tall';
          } elseif ($size === 'wide') {
              $cls .= ' gallery__item--wide';
          }
      ?>
      <figure class="<?php echo esc_attr($cls); ?>"><img src="<?php echo esc_url($img_src); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy"><?php if ($alt !== '') : ?><figcaption><?php echo esc_html($alt); ?></figcaption><?php endif; ?></figure>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
