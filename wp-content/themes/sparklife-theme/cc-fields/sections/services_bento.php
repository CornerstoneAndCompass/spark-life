<?php
/**
 * Section: services_bento — the home page bento grid of services.
 *
 * Content comes from the Services custom post type, not from fields, so the
 * grid always matches what the business offers. Per-service flags drive the
 * layout: "Feature on home page" → the tall dark tile (rendered first),
 * "Accent card" → the blue tile.
 */
if (!defined('ABSPATH')) exit;

$bg        = sl_field($section_data, 'bg', 'white');
$kicker    = sl_field($section_data, 'kicker');
$heading   = sl_field($section_data, 'heading');
$highlight = sl_field($section_data, 'heading_highlight');
$intro     = sl_field($section_data, 'intro');

$limit     = (int) sl_field($section_data, 'limit', '0');
$category  = sl_field($section_data, 'category');

$note      = sl_field($section_data, 'note');
$note_text = sl_field($section_data, 'note_link_text');
$note_url  = sl_link(sl_field($section_data, 'note_link_url'), '/services/');

$services = sl_get_services(array('limit' => $limit, 'category' => $category));
if (!$services) return;

// Featured tiles lead the grid so the tall card lands in the top-left cell.
usort($services, function ($a, $b) {
    return ($b['featured'] ? 1 : 0) - ($a['featured'] ? 1 : 0);
});
?>
<section class="section section--<?php echo esc_attr($bg); ?> services" id="services">
  <div class="wrap">
    <div class="section__head">
      <?php if ($kicker !== '') : ?><span class="kicker"><?php echo esc_html($kicker); ?></span><?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="section__title"><?php echo wp_kses_post(sl_highlight($heading, $highlight)); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?><p class="section__sub"><?php echo esc_html($intro); ?></p><?php endif; ?>
    </div>

    <div class="bento">
      <?php foreach ($services as $s) :
        $badge = sl_service_meta($s['id'], 'badge');
        $class = 'svc';
        if ($s['featured']) $class .= ' svc--feature';
        elseif ($s['accent']) $class .= ' svc--accent';
      ?>
      <article class="<?php echo esc_attr($class); ?>">
        <a class="svc__link" href="<?php echo esc_url($s['url']); ?>">
          <span class="svc__icon"><?php echo sl_icon($s['icon'], 28); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
          <h3><?php echo esc_html($s['title']); ?></h3>
          <?php if ($s['excerpt'] !== '') : ?><p><?php echo esc_html($s['excerpt']); ?></p><?php endif; ?>
          <?php if ($badge !== '') : ?>
          <span class="svc__tag<?php echo ($s['accent'] || $s['featured']) ? ' svc__tag--light' : ''; ?>"><?php echo esc_html($badge); ?></span>
          <?php elseif ($s['price_from'] !== '') : ?>
          <span class="svc__tag<?php echo ($s['accent'] || $s['featured']) ? ' svc__tag--light' : ''; ?>">
            <?php printf(esc_html__('From %s', 'sparklife'), esc_html($s['price_from'])); ?>
          </span>
          <?php endif; ?>
        </a>
      </article>
      <?php endforeach; ?>
    </div>

    <?php if ($note !== '' || $note_text !== '') : ?>
    <p class="services__note">
      <?php echo esc_html($note); ?>
      <?php if ($note_text !== '') : ?>
      <a href="<?php echo esc_url($note_url); ?>"><?php echo esc_html($note_text); ?></a>
      <?php endif; ?>
    </p>
    <?php endif; ?>
  </div>
</section>
