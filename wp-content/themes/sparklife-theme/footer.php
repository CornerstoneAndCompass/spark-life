<?php
/**
 * Footer — brand column, live Services list, company links, contact details,
 * legal bar and the sticky mobile call bar.
 */
if (!defined('ABSPATH')) exit;

$tel     = sl_tel();
$phone   = sl_get_var('company_phone');
$email   = sl_get_var('company_email');
$address = sl_get_var('company_address');
$hours   = sl_get_var('company_hours');
$abn     = sl_get_var('company_abn');
$rec     = sl_get_var('rec_license');
$fb      = sl_get_var('facebook_url');
$ig      = sl_get_var('instagram_url');
$score   = sl_get_var('review_score');
$name    = sl_get_var('company_name', get_bloginfo('name'));
$short   = sl_get_var('company_short', $name);
$region  = sl_get_var('company_region');
$logo    = sl_logo_url();

$services = sl_get_services(array('limit' => 6));

$company_links = array(
    array('label' => __('About Spark Life', 'sparklife'), 'url' => home_url('/about/')),
    array('label' => __('Our projects', 'sparklife'),     'url' => home_url('/projects/')),
    array('label' => __('Service areas', 'sparklife'),    'url' => home_url('/service-areas/')),
    array('label' => __('Contact', 'sparklife'),          'url' => home_url('/contact/')),
);
?>
</main>

<footer class="footer">
  <div class="wrap footer__grid">
    <div class="footer__brand">
      <a class="brand brand--footer" href="<?php echo esc_url(home_url('/')); ?>">
        <img class="brand__mark" src="<?php echo esc_url($logo); ?>" alt="" width="46" height="46">
        <span class="brand__text">
          <span class="brand__name"><?php echo esc_html($short); ?></span>
          <span class="brand__sub"><?php esc_html_e('Electrical Contractors', 'sparklife'); ?></span>
        </span>
      </a>
      <p><?php
        printf(
          /* translators: %s: the service region, e.g. "Frankston, Bayside & the Mornington Peninsula". */
          esc_html__('Friendly, reliable residential electricians serving %s.', 'sparklife'),
          esc_html($region)
        );
      ?></p>
      <?php if ($score) : ?>
      <div class="footer__rating">
        <span class="stars" aria-hidden="true">★★★★★</span>
        <?php printf(esc_html__('Rated %s/5 by local homeowners', 'sparklife'), esc_html($score)); ?>
      </div>
      <?php endif; ?>
      <?php if ($fb || $ig) : ?>
      <div class="footer__social">
        <?php if ($fb) : ?><a href="<?php echo esc_url($fb); ?>" target="_blank" rel="noopener" aria-label="Facebook"><?php echo sl_icon('facebook', 20); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a><?php endif; ?>
        <?php if ($ig) : ?><a href="<?php echo esc_url($ig); ?>" target="_blank" rel="noopener" aria-label="Instagram"><?php echo sl_icon('instagram', 20); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="footer__col">
      <h4><?php esc_html_e('Services', 'sparklife'); ?></h4>
      <?php foreach ($services as $s) : ?>
      <a href="<?php echo esc_url($s['url']); ?>"><?php echo esc_html($s['title']); ?></a>
      <?php endforeach; ?>
      <a href="<?php echo esc_url(home_url('/services/')); ?>"><?php esc_html_e('All services', 'sparklife'); ?></a>
    </div>

    <div class="footer__col">
      <h4><?php esc_html_e('Company', 'sparklife'); ?></h4>
      <?php foreach ($company_links as $l) : ?>
      <a href="<?php echo esc_url($l['url']); ?>"><?php echo esc_html($l['label']); ?></a>
      <?php endforeach; ?>
    </div>

    <div class="footer__col footer__col--contact">
      <h4><?php esc_html_e('Get in touch', 'sparklife'); ?></h4>
      <?php if ($phone) : ?><a href="tel:<?php echo esc_attr($tel); ?>"><?php echo esc_html($phone); ?></a><?php endif; ?>
      <?php if ($email) : ?><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a><?php endif; ?>
      <?php if ($address) : ?>
      <address><?php echo wp_kses_post(nl2br(esc_html(str_replace(', ', ",\n", $address)))); ?></address>
      <?php endif; ?>
      <?php if ($hours) : ?>
      <p class="footer__hours"><?php echo esc_html(implode(' · ', sl_lines($hours))); ?></p>
      <?php endif; ?>
    </div>
  </div>

  <div class="wrap footer__bar">
    <p>&copy; <span id="year"><?php echo esc_html(wp_date('Y')); ?></span>
      <?php echo esc_html($name); ?>
      <?php if ($rec) : ?>· <?php echo esc_html($rec); ?><?php endif; ?>
      <?php if ($abn) : ?>· <?php printf(esc_html__('ABN %s', 'sparklife'), esc_html($abn)); ?><?php endif; ?>
    </p>
    <p class="footer__legal">
      <a href="<?php echo esc_url(home_url('/privacy/')); ?>"><?php esc_html_e('Privacy', 'sparklife'); ?></a> ·
      <a href="<?php echo esc_url(home_url('/terms/')); ?>"><?php esc_html_e('Terms', 'sparklife'); ?></a>
    </p>
  </div>
</footer>

<?php if ($phone) : ?>
<a class="callbar" href="tel:<?php echo esc_attr($tel); ?>">
  <?php echo sl_icon('phone', 18); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
  <?php printf(esc_html__('Tap to call %1$s · %2$s', 'sparklife'), esc_html($short), esc_html($phone)); ?>
</a>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
