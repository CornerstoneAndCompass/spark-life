<?php
/**
 * CC Fields section template: Contact (info + form).
 *
 * Mirrors the build_contact() contact block and contact_form() in
 * calltheplumberguy/build.py: a .section > .wrap.contact__in two-column layout —
 * left .contact__info (eyebrow, .contact__title, .contact__lead, a .contact__list
 * of phone / mobile / email / hours / area / social, and .contact__badges
 * credentials) and right .contact__formwrap holding the enquiry form .cform.js-form
 * that posts to CC Fields (action=ccf_submit_form).
 *
 * Receives $section_data — keys are the field 'name's defined for the 'contact'
 * section type in includes/class-cc-sections.php:
 *   bg, eyebrow, heading, intro, form_title, form_note, form_button_text.
 *
 * Contact-list values (mobile, email, hours, area, social) come from global vars
 * via ccf_get_var(), matching the static site's hard-coded contact details.
 */
if (!defined('ABSPATH')) exit;

// ── Section fields (read every key defensively) ─────────────────────────────
$bg               = isset($section_data['bg']) ? $section_data['bg'] : 'white';
$eyebrow          = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$heading          = isset($section_data['heading']) ? $section_data['heading'] : '';
$intro            = isset($section_data['intro']) ? $section_data['intro'] : '';
$form_title       = isset($section_data['form_title']) ? $section_data['form_title'] : '';
$form_note        = isset($section_data['form_note']) ? $section_data['form_note'] : '';
$form_button_text = isset($section_data['form_button_text']) ? $section_data['form_button_text'] : '';

// ── Global contact vars ─────────────────────────────────────────────────────
$company_phone  = ccf_get_var('company_phone');
$company_tel    = ccf_get_var('company_tel');
$company_mobile = ccf_get_var('company_mobile');
$company_email  = ccf_get_var('company_email');
$company_hours  = ccf_get_var('company_hours');
$company_area   = ccf_get_var('company_address');
$facebook_url   = ccf_get_var('facebook_url');
$instagram_url  = ccf_get_var('instagram_url');

$tel_href        = 'tel:' . preg_replace('/[^0-9+]/', '', $company_tel);
$mobile_tel_href = 'tel:' . preg_replace('/[^0-9+]/', '', $company_mobile);

// Hours are stored newline-separated; the static site joins parts with " · ".
$hours_html = $company_hours !== ''
    ? implode(' &middot; ', array_map('esc_html', preg_split('/\r\n|\r|\n/', trim($company_hours))))
    : '';

// Credential badges shown beneath the contact list (mirror build.py).
$badges = array('Fully Insured', 'NDIS Registered', 'Family Business Since 2007');

// Services for the "I need help with" select — mirrors service_select() / SERVICES
// in build.py (same eight services as the home hero leadform).
$services = array(
    'Hot Water Systems',
    'Blocked Drains & CCTV',
    'Gas Services',
    'Emergency Plumbing',
    'NDIS & Home Modifications',
    'General Plumbing',
    'Plumbing Inspections',
    'Heating & Cooling',
);
?>
<section class="section section--<?php echo esc_attr($bg); ?> reveal">
  <div class="wrap contact__in">
    <div class="contact__info reveal">
      <?php if ($eyebrow !== '') : ?>
      <p class="eyebrow"><?php echo esc_html($eyebrow); ?></p>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="contact__title"><?php echo esc_html($heading); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?>
      <p class="contact__lead"><?php echo esc_html($intro); ?></p>
      <?php endif; ?>
      <ul class="contact__list">
        <?php if ($company_phone !== '') : ?>
        <li><span class="contact__label">Phone</span>
          <a class="contact__big" href="<?php echo esc_attr($tel_href); ?>"><?php echo esc_html($company_phone); ?></a></li>
        <?php endif; ?>
        <?php if ($company_mobile !== '') : ?>
        <li><span class="contact__label">Mobile</span>
          <a href="<?php echo esc_attr($mobile_tel_href); ?>"><?php echo esc_html($company_mobile); ?></a></li>
        <?php endif; ?>
        <?php if ($company_email !== '') : ?>
        <li><span class="contact__label">Email</span>
          <a href="mailto:<?php echo esc_attr($company_email); ?>"><?php echo esc_html($company_email); ?></a></li>
        <?php endif; ?>
        <?php if ($hours_html !== '') : ?>
        <li><span class="contact__label">Hours</span>
          <span><?php echo wp_kses_post($hours_html); ?></span></li>
        <?php endif; ?>
        <?php if ($company_area !== '') : ?>
        <li><span class="contact__label">Area</span>
          <span><?php echo esc_html($company_area); ?></span></li>
        <?php endif; ?>
        <?php if ($facebook_url !== '' || $instagram_url !== '') : ?>
        <li><span class="contact__label">Social</span>
          <span><?php
            $social = array();
            if ($facebook_url !== '') {
                $social[] = '<a href="' . esc_url($facebook_url) . '" target="_blank" rel="noopener">Facebook</a>';
            }
            if ($instagram_url !== '') {
                $social[] = '<a href="' . esc_url($instagram_url) . '" target="_blank" rel="noopener">Instagram</a>';
            }
            echo wp_kses_post(implode(' &nbsp;&middot;&nbsp; ', $social));
          ?></span></li>
        <?php endif; ?>
      </ul>
      <div class="contact__badges">
        <?php foreach ($badges as $badge) : ?>
        <span><?php echo esc_html($badge); ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="contact__formwrap reveal">
      <form class="cform js-form" id="contactForm" method="post" action="" novalidate>
        <input type="hidden" name="action" value="ccf_submit_form">
        <input type="hidden" name="page_id" value="<?php echo esc_attr(get_the_ID()); ?>">
        <input type="hidden" name="ccf_form_nonce" value="<?php echo esc_attr(wp_create_nonce('ccf_form_submit')); ?>">
        <input type="text" name="ccf_hp" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">
        <h3 class="cform__title"><?php echo esc_html($form_title !== '' ? $form_title : 'Send an enquiry'); ?></h3>
        <p class="cform__sub"><?php echo esc_html($form_note !== '' ? $form_note : "We'll get back to you as quickly as we can."); ?></p>
        <div class="cform__row">
          <label class="field"><span>Your name</span><input type="text" name="name" autocomplete="name" required></label>
          <label class="field"><span>Phone</span><input type="tel" name="phone" autocomplete="tel" required></label>
        </div>
        <div class="cform__row">
          <label class="field"><span>Suburb</span><input type="text" name="suburb" autocomplete="address-level2"></label>
          <label class="field"><span>I need help with</span>
            <select name="service">
              <?php foreach ($services as $service) : ?>
              <option><?php echo esc_html($service); ?></option>
              <?php endforeach; ?>
              <option>Something else</option>
            </select>
          </label>
        </div>
        <label class="field"><span>Tell us a little more</span>
          <textarea name="message" rows="4" placeholder="What's going on, and when suits you?"></textarea></label>
        <button type="submit" class="btn btn--primary btn--block btn--lg"><?php echo esc_html($form_button_text !== '' ? $form_button_text : 'Send enquiry'); ?></button>
        <?php if ($company_phone !== '') : ?>
        <p class="cform__note">Prefer to talk? Call <a href="<?php echo esc_attr($tel_href); ?>"><?php echo esc_html($company_phone); ?></a>. We'll get back to you as quickly as we can.</p>
        <?php endif; ?>
        <p class="cform__success" hidden>Thanks, we've got it. We'll be in touch as soon as we can.<?php echo $company_phone !== '' ? ' For anything urgent, call <a href="' . esc_attr($tel_href) . '">' . esc_html($company_phone) . '</a> now.' : ''; ?></p>
      </form>
    </div>
  </div>
</section>
