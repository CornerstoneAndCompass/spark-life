<?php
/**
 * Section: embed_map — embedded Google map of the service area.
 */
if (!defined('ABSPATH')) exit;

$bg      = sl_field($section_data, 'bg', 'paper');
$kicker  = sl_field($section_data, 'kicker');
$heading = sl_field($section_data, 'heading');
$url     = sl_field($section_data, 'embed_url');
$is_ink  = ($bg === 'ink' || $bg === 'blue');
if ($url === '') return;
?>
<section class="section section--<?php echo esc_attr($bg); ?>">
  <div class="wrap">
    <?php if ($kicker !== '' || $heading !== '') : ?>
    <div class="section__head">
      <?php if ($kicker !== '') : ?>
      <span class="kicker<?php echo $is_ink ? ' kicker--light' : ''; ?>"><?php echo esc_html($kicker); ?></span>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="section__title<?php echo $is_ink ? ' section__title--light' : ''; ?>"><?php echo wp_kses_post(sl_highlight($heading, '')); ?></h2>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="mapwrap">
      <iframe src="<?php echo esc_url($url); ?>" title="<?php esc_attr_e('Map of our service area', 'sparklife'); ?>"
              loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
    </div>
  </div>
</section>
