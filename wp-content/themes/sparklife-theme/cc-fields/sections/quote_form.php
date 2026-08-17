<?php
/**
 * Section: quote_form — contact details beside the enquiry form.
 *
 * The form posts to CC Fields' admin-ajax handler (action=ccf_submit_form),
 * which emails the company address and keeps a copy on the page. The service
 * dropdown is built from live Services, and on a service page that service is
 * pre-selected so the enquiry arrives already labelled.
 */
if (!defined('ABSPATH')) exit;

$bg        = sl_field($section_data, 'bg', 'paper');
$anchor    = sanitize_title(sl_field($section_data, 'anchor', 'quote'));
$kicker    = sl_field($section_data, 'kicker');
$heading   = sl_field($section_data, 'heading');
$highlight = sl_field($section_data, 'heading_highlight');
$intro     = sl_field($section_data, 'intro');
$is_ink    = ($bg === 'ink' || $bg === 'blue');

$show_details = sl_on(sl_field($section_data, 'show_details', '1'), true);
$btn_text     = sl_field($section_data, 'form_button_text', __('Send my request ⚡', 'sparklife'));
$form_note    = sl_field($section_data, 'form_note');

$phone   = sl_get_var('company_phone');
$email   = sl_get_var('company_email');
$suburb  = sl_get_var('company_suburb');

$services = sl_service_options();
$preselect = sl_on(sl_field($section_data, 'preselect_service', '1'), true) && is_singular('service')
    ? get_the_title()
    : '';
?>
<section class="section section--<?php echo esc_attr($bg); ?> quote-sec" id="<?php echo esc_attr($anchor); ?>">
  <div class="wrap quote-sec__grid">
    <div class="quote-sec__copy">
      <?php if ($kicker !== '') : ?>
      <span class="kicker<?php echo $is_ink ? ' kicker--light' : ''; ?>"><?php echo esc_html($kicker); ?></span>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="section__title<?php echo $is_ink ? ' section__title--light' : ''; ?>"><?php echo wp_kses_post(sl_highlight($heading, $highlight)); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?><p><?php echo esc_html($intro); ?></p><?php endif; ?>

      <?php if ($show_details && ($phone || $email)) : ?>
      <div class="quote-sec__contact">
        <?php if ($phone) : ?>
        <a class="contact-row" href="<?php echo esc_attr(sl_tel_href()); ?>">
          <span class="contact-row__ic"><?php echo sl_icon('phone', 21); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
          <span><small><?php esc_html_e('Call or text', 'sparklife'); ?></small><strong><?php echo esc_html($phone); ?></strong></span>
        </a>
        <?php endif; ?>
        <?php if ($email) : ?>
        <a class="contact-row" href="mailto:<?php echo esc_attr($email); ?>">
          <span class="contact-row__ic"><?php echo sl_icon('mail', 21); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
          <span><small><?php esc_html_e('Email us', 'sparklife'); ?></small><strong><?php echo esc_html($email); ?></strong></span>
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <form class="qform js-form" method="post" action="" novalidate>
      <input type="hidden" name="action" value="ccf_submit_form">
      <input type="hidden" name="page_id" value="<?php echo esc_attr(get_the_ID()); ?>">
      <input type="hidden" name="ccf_form_nonce" value="<?php echo esc_attr(wp_create_nonce('ccf_form_submit')); ?>">
      <input type="text" name="ccf_hp" value="" tabindex="-1" autocomplete="off" aria-hidden="true"
             style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">

      <div class="qform__row">
        <label class="field">
          <span><?php esc_html_e('Your name', 'sparklife'); ?></span>
          <input type="text" name="name" autocomplete="name" placeholder="<?php esc_attr_e('Jane Citizen', 'sparklife'); ?>" required>
        </label>
        <label class="field">
          <span><?php esc_html_e('Phone', 'sparklife'); ?></span>
          <input type="tel" name="phone" autocomplete="tel" placeholder="<?php echo esc_attr($phone); ?>" required>
        </label>
      </div>
      <label class="field">
        <span><?php esc_html_e('Suburb', 'sparklife'); ?></span>
        <input type="text" name="suburb" autocomplete="address-level2" placeholder="<?php echo esc_attr($suburb); ?>">
      </label>
      <label class="field">
        <span><?php esc_html_e('What do you need?', 'sparklife'); ?></span>
        <select name="service">
          <option value=""><?php esc_html_e('Choose a service…', 'sparklife'); ?></option>
          <?php foreach ($services as $service) : ?>
          <option <?php selected($preselect, $service); ?>><?php echo esc_html($service); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        <span><?php esc_html_e('A few details', 'sparklife'); ?></span>
        <textarea name="message" rows="3" placeholder="<?php esc_attr_e('Tell us what’s going on…', 'sparklife'); ?>"></textarea>
      </label>

      <button type="submit" class="btn btn--primary btn--lg btn--block"><?php echo esc_html($btn_text); ?></button>
      <?php if ($form_note !== '') : ?>
      <p class="qform__note"><?php echo esc_html($form_note); ?></p>
      <?php endif; ?>
      <p class="qform__ok cform__success" hidden>
        <?php esc_html_e('Thanks, we’ve got it — we’ll be in touch shortly.', 'sparklife'); ?>
        <?php if ($phone) : ?>
        <?php printf(
          /* translators: %s: phone link. */
          wp_kses(__('Need us urgently? Call %s.', 'sparklife'), array('a' => array('href' => array()))),
          '<a href="' . esc_attr(sl_tel_href()) . '">' . esc_html($phone) . '</a>'
        ); ?>
        <?php endif; ?>
      </p>
    </form>
  </div>
</section>
