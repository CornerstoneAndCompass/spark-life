<?php
/**
 * Section: cta_band — dark closing call-to-action with the blue glow.
 */
if (!defined('ABSPATH')) exit;

$title = sl_field($section_data, 'title');
$lead  = sl_field($section_data, 'lead');

$p_text = sl_field($section_data, 'primary_btn_text', __('Get your free quote', 'sparklife'));
$p_url  = sl_link(sl_field($section_data, 'primary_btn_url'), '/contact/');
$s_text = sl_field($section_data, 'secondary_btn_text');
$s_url  = sl_field($section_data, 'secondary_btn_url');

$phone    = sl_get_var('company_phone');
$s_is_tel = ($s_url === '');
$s_url    = $s_is_tel ? sl_tel_href() : sl_link($s_url);
if ($s_text === '' && $phone) $s_text = sprintf(__('Call %s', 'sparklife'), $phone);
if ($title === '') return;
?>
<section class="cta-band">
  <div class="wrap cta-band__inner">
    <h2><?php echo wp_kses_post(sl_highlight($title, '')); ?></h2>
    <?php if ($lead !== '') : ?><p class="cta-band__lead"><?php echo esc_html($lead); ?></p><?php endif; ?>
    <div class="cta-band__actions">
      <?php if ($p_text !== '') : ?>
      <a class="btn btn--primary btn--lg" href="<?php echo esc_url($p_url); ?>"><?php echo esc_html($p_text); ?></a>
      <?php endif; ?>
      <?php if ($s_text !== '' && $s_url !== '') : ?>
      <a class="btn btn--ghost-light btn--lg" href="<?php echo esc_attr($s_url); ?>"><?php echo esc_html($s_text); ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>
