<?php
/**
 * Template: single Service.
 *
 * A service page is built from CC Fields sections like any other page. When a
 * newly added service has no sections yet, we compose a complete, on-brand page
 * from what the service itself already carries (title, tagline, editor content,
 * featured image) plus the standard closing blocks — so adding a service in
 * wp-admin never produces a half-finished page.
 */
if (!defined('ABSPATH')) exit;
get_header(); ?>

<?php while (have_posts()) : the_post();
    $pid = get_the_ID();

    if (sl_has_sections($pid)) :
        sl_render_sections($pid);
    else :
        $tagline = sl_service_meta($pid, 'tagline');
        $summary = sl_service_meta($pid, 'summary') ?: get_the_excerpt();
        $image   = get_the_post_thumbnail_url($pid, 'large') ?: sl_service_meta($pid, 'image_url');
        $content = trim(get_the_content());

        sl_render_section('pagehero', array(
            'eyebrow'            => __('Our services', 'sparklife'),
            'title'              => get_the_title(),
            'lead'               => $tagline ?: $summary,
            'show_actions'       => '1',
            'primary_btn_text'   => __('Get a free quote', 'sparklife'),
            'primary_btn_url'    => '/contact/',
            'secondary_btn_text' => sl_get_var('company_phone') ? __('Call us now', 'sparklife') : '',
            'show_breadcrumb'    => '1',
        ));

        if ($content !== '' || $image) {
            sl_render_section('service_row', array(
                'bg'          => 'white',
                'kicker'      => __('What’s involved', 'sparklife'),
                'heading'     => get_the_title(),
                'body'        => apply_filters('the_content', $content),
                'image_url'   => $image,
                'image_right' => '1',
            ));
        }

        sl_render_section('quote_form', array(
            'bg'      => 'paper',
            'anchor'  => 'quote',
            'kicker'  => __('Free quote', 'sparklife'),
            'heading' => sprintf(__('Need a hand with %s?', 'sparklife'), get_the_title()),
            'intro'   => __('Tell us what’s going on and we’ll come back to you with a clear, fixed price — usually within the hour during business times.', 'sparklife'),
            'preselect_service' => '1',
        ));

        sl_render_section('services_list', array(
            'bg'              => 'white',
            'kicker'          => __('More from Spark Life', 'sparklife'),
            'heading'         => __('Other things we do.', 'sparklife'),
            'exclude_current' => '1',
            'limit'           => '6',
        ));

        sl_render_section('cta_band', array(
            'title' => __('Ready to spark some life into your home?', 'sparklife'),
        ));
    endif;
endwhile; ?>

<?php get_footer(); ?>
