<?php
/**
 * Section: why_stats — dark "why us" block with ticked reasons and stat tiles.
 * The stat numbers count up on scroll (assets/js/main.js).
 */
if (!defined('ABSPATH')) exit;

$bg      = sl_field($section_data, 'bg', 'ink');
$kicker  = sl_field($section_data, 'kicker');
$heading = sl_field($section_data, 'heading');
$intro   = sl_field($section_data, 'intro');
$is_ink  = ($bg === 'ink' || $bg === 'blue');

$items = (isset($section_data['items']) && is_array($section_data['items'])) ? $section_data['items'] : array();
$stats = (isset($section_data['stats']) && is_array($section_data['stats'])) ? $section_data['stats'] : array();
?>
<section class="section section--<?php echo esc_attr($bg); ?> why">
  <div class="wrap why__grid">
    <div class="why__copy">
      <?php if ($kicker !== '') : ?>
      <span class="kicker<?php echo $is_ink ? ' kicker--light' : ''; ?>"><?php echo esc_html($kicker); ?></span>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="section__title<?php echo $is_ink ? ' section__title--light' : ''; ?>"><?php echo wp_kses_post(sl_highlight($heading, '')); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?><p class="why__lead"><?php echo esc_html($intro); ?></p><?php endif; ?>

      <?php if ($items) : ?>
      <ul class="why__list">
        <?php foreach ($items as $item) :
          $title = isset($item['title']) ? $item['title'] : '';
          $desc  = isset($item['desc']) ? $item['desc'] : '';
          if ($title === '' && $desc === '') continue;
        ?>
        <li>
          <?php echo sl_icon('check', 26); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          <div><?php if ($title !== '') : ?><strong><?php echo esc_html($title); ?></strong> <?php endif; ?><?php echo esc_html($desc); ?></div>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>

    <?php if ($stats) : ?>
    <div class="stats">
      <?php foreach ($stats as $stat) :
        $number   = isset($stat['number']) ? $stat['number'] : '';
        $suffix   = isset($stat['suffix']) ? $stat['suffix'] : '';
        $decimals = isset($stat['decimals']) ? (int) $stat['decimals'] : 0;
        $label    = isset($stat['label']) ? $stat['label'] : '';
        if ($number === '' && $label === '') continue;
      ?>
      <div class="stat">
        <span class="stat__num"
              data-count="<?php echo esc_attr($number); ?>"
              data-suffix="<?php echo esc_attr($suffix); ?>"
              data-decimals="<?php echo esc_attr($decimals); ?>">0</span>
        <span class="stat__label"><?php echo esc_html($label); ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
