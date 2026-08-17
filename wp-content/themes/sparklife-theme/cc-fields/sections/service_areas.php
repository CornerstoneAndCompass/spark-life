<?php
/**
 * Section: service_areas — blue band listing the suburbs covered.
 */
if (!defined('ABSPATH')) exit;

$kicker  = sl_field($section_data, 'kicker');
$heading = sl_field($section_data, 'heading');
$intro   = sl_field($section_data, 'intro');
$suburbs = sl_lines(sl_field($section_data, 'suburbs'));
if (!$suburbs) return;
?>
<section class="section area" id="area">
  <div class="wrap area__inner">
    <div class="area__copy">
      <?php if ($kicker !== '') : ?><span class="kicker kicker--light"><?php echo esc_html($kicker); ?></span><?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="section__title section__title--light"><?php echo wp_kses_post(sl_highlight($heading, '')); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?><p class="area__lead"><?php echo esc_html($intro); ?></p><?php endif; ?>
    </div>
    <ul class="area__list">
      <?php foreach ($suburbs as $suburb) : ?>
      <li><?php echo esc_html($suburb); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
