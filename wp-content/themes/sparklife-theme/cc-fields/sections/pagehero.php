<?php
/**
 * Section: pagehero — inner-page header (dark, blue glow, optional image wash).
 */
if (!defined('ABSPATH')) exit;

$eyebrow   = sl_field($section_data, 'eyebrow');
$title     = sl_field($section_data, 'title');
$highlight = sl_field($section_data, 'title_highlight');
$lead      = sl_field($section_data, 'lead');

$bg_image = sl_image($section_data, 'bg_image');

$show_actions = sl_on(sl_field($section_data, 'show_actions', '1'), true);
$p_text = sl_field($section_data, 'primary_btn_text', __('Get a quote', 'sparklife'));
$p_url  = sl_link(sl_field($section_data, 'primary_btn_url'), '/contact/');
$s_text = sl_field($section_data, 'secondary_btn_text');
$s_url  = sl_field($section_data, 'secondary_btn_url');

$phone    = sl_get_var('company_phone');
$s_is_tel = ($s_url === '');
$s_url    = $s_is_tel ? sl_tel_href() : sl_link($s_url);
if ($s_text === '' && $phone) $s_text = sprintf(__('Call %s', 'sparklife'), $phone);

$show_crumb = sl_on(sl_field($section_data, 'show_breadcrumb', '1'), true);
?>
<section class="pagehero<?php echo $bg_image ? ' pagehero--image' : ''; ?>">
  <div class="pagehero__glow" aria-hidden="true"></div>
  <?php if ($bg_image) : ?>
  <div class="pagehero__bg" aria-hidden="true"><img src="<?php echo esc_url($bg_image); ?>" alt="" loading="lazy"></div>
  <?php endif; ?>
  <div class="wrap pagehero__inner">
    <?php if ($show_crumb && !is_front_page()) : ?>
    <nav class="crumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'sparklife'); ?>">
      <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'sparklife'); ?></a>
      <?php if (is_singular('service')) : ?>
        <span aria-hidden="true">/</span>
        <a href="<?php echo esc_url(home_url('/services/')); ?>"><?php esc_html_e('Services', 'sparklife'); ?></a>
      <?php endif; ?>
      <span aria-hidden="true">/</span>
      <span aria-current="page"><?php echo esc_html(get_the_title()); ?></span>
    </nav>
    <?php endif; ?>

    <?php if ($eyebrow !== '') : ?>
    <span class="eyebrow eyebrow--light"><?php echo esc_html($eyebrow); ?></span>
    <?php endif; ?>

    <?php if ($title !== '') : ?>
    <h1 class="pagehero__title"><?php echo wp_kses_post(sl_highlight($title, $highlight)); ?></h1>
    <?php endif; ?>

    <?php if ($lead !== '') : ?>
    <p class="pagehero__lead"><?php echo esc_html($lead); ?></p>
    <?php endif; ?>

    <?php if ($show_actions && ($p_text !== '' || $s_text !== '')) : ?>
    <div class="pagehero__actions">
      <?php if ($p_text !== '') : ?>
      <a class="btn btn--primary btn--lg" href="<?php echo esc_url($p_url); ?>"><?php echo esc_html($p_text); ?></a>
      <?php endif; ?>
      <?php if ($s_text !== '' && $s_url !== '') : ?>
      <a class="btn btn--ghost-light btn--lg" href="<?php echo esc_attr($s_url); ?>"><?php echo esc_html($s_text); ?></a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
