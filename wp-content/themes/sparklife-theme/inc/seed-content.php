<?php
/**
 * Seed-content provider.
 *
 * Reads data/content.json — every page, every service and every global variable
 * for the site — and hands it to the CC Fields seeder through the ccf_seed_*
 * filters. Run **CC Fields → Seed / Rebuild** in wp-admin to create or refresh
 * everything; it is idempotent, matching on slug + post type and updating in
 * place rather than duplicating.
 *
 * Image paths in the JSON use the {{THEME}} placeholder, resolved here to the
 * theme URL so assets are served from assets/img/.
 */
if (!defined('ABSPATH')) exit;

function sl_seed_data() {
    static $data = null;
    if ($data !== null) return $data;

    $file = SL_PATH . '/data/content.json';
    if (!file_exists($file)) { $data = array(); return $data; }

    $raw = file_get_contents($file);
    $raw = str_replace('{{THEME}}', untrailingslashit(SL_URL), $raw);
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $data = array();
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[sparklife] data/content.json is not valid JSON: ' . json_last_error_msg());
        }
        return $data;
    }
    $data = $decoded;
    return $data;
}

/**
 * Pages first, then services. The seeder walks the list in order, so services
 * are created after the /services/ page they belong under.
 */
add_filter('ccf_seed_pages', function ($pages) {
    $d = sl_seed_data();
    $out = array();

    if (!empty($d['pages']) && is_array($d['pages'])) {
        $out = array_merge($out, $d['pages']);
    }
    if (!empty($d['services']) && is_array($d['services'])) {
        foreach ($d['services'] as $service) {
            $service['post_type'] = 'service';
            $out[] = $service;
        }
    }
    return $out ? $out : $pages;
});

add_filter('ccf_seed_vars', function ($vars) {
    $d = sl_seed_data();
    $seed = (!empty($d['vars']) && is_array($d['vars'])) ? $d['vars'] : array();
    // Theme defaults are the baseline; the JSON layers on top, then anything
    // another filter has already contributed.
    return array_merge(sl_default_vars(), $seed, is_array($vars) ? $vars : array());
});

add_filter('ccf_seed_front', function ($front) {
    $d = sl_seed_data();
    return isset($d['front_page']) ? $d['front_page'] : $front;
});

/**
 * Seeding creates `service` posts, so the CPT's rewrite rules must exist before
 * their permalinks resolve. The seeder flushes at the end of its run; this makes
 * sure the post type is registered by then even if the seeder runs early.
 */
add_action('admin_init', function () {
    if (!post_type_exists('service')) {
        sl_register_service_cpt();
    }
}, 5);
