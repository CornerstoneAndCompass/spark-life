<?php
/**
 * Template: 404.
 */
if (!defined('ABSPATH')) exit;
get_header();
$phone = sl_get_var('company_phone'); ?>

<section class="pagehero">
  <div class="pagehero__glow" aria-hidden="true"></div>
  <div class="wrap pagehero__inner">
    <span class="eyebrow eyebrow--light">404</span>
    <h1 class="pagehero__title"><?php esc_html_e('We couldn’t find that page.', 'sparklife'); ?></h1>
    <p class="pagehero__lead"><?php esc_html_e('It may have moved. Head back home, browse our services, or give us a bell and we’ll point you the right way.', 'sparklife'); ?></p>
    <div class="pagehero__actions">
      <a class="btn btn--primary btn--lg" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Back to home', 'sparklife'); ?></a>
      <?php if ($phone) : ?>
      <a class="btn btn--ghost-light btn--lg" href="<?php echo esc_attr(sl_tel_href()); ?>"><?php printf(esc_html__('Call %s', 'sparklife'), esc_html($phone)); ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php get_footer(); ?>
