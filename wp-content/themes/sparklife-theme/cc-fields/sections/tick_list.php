<?php
/**
 * Section: tick_list — two-column checklist of what a job includes.
 */
if (!defined('ABSPATH')) exit;

$bg        = sl_field($section_data, 'bg', 'paper');
$kicker    = sl_field($section_data, 'kicker');
$heading   = sl_field($section_data, 'heading');
$highlight = sl_field($section_data, 'heading_highlight');
$intro     = sl_field($section_data, 'intro');
$is_ink    = ($bg === 'ink' || $bg === 'blue');

$items = array();
if (!empty($section_data['items']) && is_array($section_data['items'])) {
    foreach ($section_data['items'] as $row) {
        $text = isset($row['text']) ? trim($row['text']) : '';
        if ($text !== '') $items[] = $text;
    }
}
if (!$items) return;
?>
<section class="section section--<?php echo esc_attr($bg); ?>">
  <div class="wrap">
    <div class="section__head">
      <?php if ($kicker !== '') : ?>
      <span class="kicker<?php echo $is_ink ? ' kicker--light' : ''; ?>"><?php echo esc_html($kicker); ?></span>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="section__title<?php echo $is_ink ? ' section__title--light' : ''; ?>"><?php echo wp_kses_post(sl_highlight($heading, $highlight)); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?><p class="section__sub"><?php echo esc_html($intro); ?></p><?php endif; ?>
    </div>
    <ul class="ticks">
      <?php foreach ($items as $text) : ?>
      <li>
        <?php echo sl_icon('check', 22); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <span><?php echo esc_html($text); ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
