<?php
/**
 * Section: process_steps — numbered "how it works" cards.
 */
if (!defined('ABSPATH')) exit;

$bg        = sl_field($section_data, 'bg', 'paper');
$kicker    = sl_field($section_data, 'kicker');
$heading   = sl_field($section_data, 'heading');
$highlight = sl_field($section_data, 'heading_highlight');
$intro     = sl_field($section_data, 'intro');
$is_ink    = ($bg === 'ink' || $bg === 'blue');

$steps = (isset($section_data['steps']) && is_array($section_data['steps'])) ? $section_data['steps'] : array();
if (!$steps) return;
?>
<section class="section section--<?php echo esc_attr($bg); ?> process">
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
    <ol class="steps">
      <?php foreach ($steps as $i => $step) :
        $title = isset($step['title']) ? $step['title'] : '';
        $desc  = isset($step['desc']) ? $step['desc'] : '';
        if ($title === '' && $desc === '') continue;
      ?>
      <li class="step">
        <span class="step__no"><?php echo esc_html(sprintf('%02d', $i + 1)); ?></span>
        <h3><?php echo esc_html($title); ?></h3>
        <p><?php echo esc_html($desc); ?></p>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
