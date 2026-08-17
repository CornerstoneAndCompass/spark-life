<?php
/**
 * Section: services_list — every live service as a linked card.
 *
 * Used on the Services landing page and, with "hide the service being viewed"
 * on, as the "other things we do" block at the bottom of a service page.
 */
if (!defined('ABSPATH')) exit;

$bg        = sl_field($section_data, 'bg', 'white');
$kicker    = sl_field($section_data, 'kicker');
$heading   = sl_field($section_data, 'heading');
$highlight = sl_field($section_data, 'heading_highlight');
$intro     = sl_field($section_data, 'intro');
$category  = sl_field($section_data, 'category');
$limit     = (int) sl_field($section_data, 'limit', '0');
$exclude   = sl_on(sl_field($section_data, 'exclude_current', '1'), true) && is_singular('service') ? get_the_ID() : 0;

$services = sl_get_services(array('limit' => $limit, 'category' => $category, 'exclude' => $exclude));
if (!$services) return;
?>
<section class="section section--<?php echo esc_attr($bg); ?>">
  <div class="wrap">
    <?php if ($kicker !== '' || $heading !== '' || $intro !== '') : ?>
    <div class="section__head">
      <?php if ($kicker !== '') : ?><span class="kicker"><?php echo esc_html($kicker); ?></span><?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="section__title<?php echo $bg === 'ink' ? ' section__title--light' : ''; ?>"><?php echo wp_kses_post(sl_highlight($heading, $highlight)); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?><p class="section__sub"><?php echo esc_html($intro); ?></p><?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="svc-grid">
      <?php foreach ($services as $i => $s) : ?>
      <a class="svc-card" href="<?php echo esc_url($s['url']); ?>">
        <span class="svc-card__num"><?php echo esc_html(sprintf('%02d', $i + 1)); ?></span>
        <span class="svc-card__icon"><?php echo sl_icon($s['icon'], 26); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
        <span class="svc-card__body">
          <strong class="svc-card__name"><?php echo esc_html($s['title']); ?></strong>
          <?php if ($s['excerpt'] !== '') : ?>
          <span class="svc-card__desc"><?php echo esc_html($s['excerpt']); ?></span>
          <?php endif; ?>
          <?php if ($s['price_from'] !== '') : ?>
          <span class="svc-card__price"><?php printf(esc_html__('From %s', 'sparklife'), esc_html($s['price_from'])); ?></span>
          <?php endif; ?>
        </span>
        <span class="svc-card__arrow" aria-hidden="true"><?php echo sl_icon('arrow', 18); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
