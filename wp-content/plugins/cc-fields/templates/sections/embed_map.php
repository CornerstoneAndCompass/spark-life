<?php if (!defined('ABSPATH')) exit;
/**
 * Section: Map Embed
 * Contact-page map: optional heading then a responsive, lazy-loaded iframe.
 * Mirrors the "Find us" map section in calltheplumberguy/build.py
 * (.section--paper > .wrap > .section__head + .mapframe > iframe).
 * Fields (from class-cc-sections.php): bg (default paper), eyebrow, heading,
 * embed_url (Google Maps embed URL).
 */

$bg        = isset($section_data['bg']) ? $section_data['bg'] : 'paper';
$eyebrow   = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$heading   = isset($section_data['heading']) ? $section_data['heading'] : '';
$embed_url = isset($section_data['embed_url']) ? $section_data['embed_url'] : '';

// Honour bg: ink lights the eyebrow + section title (paper/white keep dark text).
$is_ink      = ($bg === 'ink');
$eyebrow_cls = 'eyebrow' . ($is_ink ? ' eyebrow--lime' : '');
$title_cls   = 'section-title' . ($is_ink ? ' section-title--light' : '');

// build.py default for the embed when none is provided in the editor.
if ($embed_url === '') {
    $embed_url = 'https://www.google.com/maps?q=Melbourne+VIC+Australia&output=embed';
}
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
    <?php if ($embed_url !== '') : ?>
    <div class="mapframe reveal">
      <iframe title="<?php echo esc_attr($heading !== '' ? $heading : 'Map'); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
        src="<?php echo esc_url($embed_url); ?>"></iframe>
    </div>
    <?php endif; ?>
  </div>
</section>
