<?php
/**
 * Template: Service category archive (/service-category/{term}/).
 *
 * The main Services landing page is a normal page at /services/; this covers the
 * category views, e.g. "Residential" or "Commercial".
 */
if (!defined('ABSPATH')) exit;
get_header();

$term = get_queried_object();
?>

<section class="pagehero">
  <div class="pagehero__glow" aria-hidden="true"></div>
  <div class="wrap pagehero__inner">
    <span class="eyebrow eyebrow--light"><?php esc_html_e('Our services', 'sparklife'); ?></span>
    <h1 class="pagehero__title"><?php echo esc_html(single_term_title('', false)); ?></h1>
    <?php if (!empty($term->description)) : ?>
    <p class="pagehero__lead"><?php echo esc_html($term->description); ?></p>
    <?php endif; ?>
  </div>
</section>

<?php
sl_render_section('services_list', array(
    'bg'       => 'white',
    'category' => isset($term->slug) ? $term->slug : '',
    'limit'    => '0',
));

sl_render_section('cta_band', array(
    'title' => __('Not sure which one you need?', 'sparklife'),
    'lead'  => __('Give us a bell — happy to talk it through and point you the right way.', 'sparklife'),
));
?>

<?php get_footer(); ?>
