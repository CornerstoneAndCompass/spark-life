<?php
/**
 * Spark Life Electrical — theme functions.
 *
 * Pairs with two plugins:
 *   • CC Fields                    — page sections (_ccf_sections) + global variables
 *   • MyMomo Connector             — remote content/SEO management from the MyMomo app
 *
 * Section *types* and section *markup* are site-specific and live in this theme
 * (inc/sections.php registers them via the ccf_registered_sections filter;
 * cc-fields/sections/*.php overrides the plugin's default templates). That keeps
 * the CC Fields plugin itself generic and auto-updatable.
 */
if (!defined('ABSPATH')) exit;

define('SL_VERSION', '1.0.1');
define('SL_PATH', get_template_directory());
define('SL_URL',  get_template_directory_uri());

/* ─── Setup ─────────────────────────────────────────────────── */
function sl_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
    add_theme_support('custom-logo', array('flex-height' => true, 'flex-width' => true));
    register_nav_menus(array(
        'primary'        => __('Primary Menu', 'sparklife'),
        'footer_company' => __('Footer: Company', 'sparklife'),
    ));
}
add_action('after_setup_theme', 'sl_setup');

/* ─── Assets ────────────────────────────────────────────────── */
function sl_enqueue() {
    $v = SL_VERSION;
    wp_enqueue_style('sl-fonts',
        'https://fonts.googleapis.com/css2?family=Anton&family=Archivo:ital,wght@0,500;0,600;0,700;0,800;1,700&family=Inter:wght@400;500;600;700&display=swap',
        array(), null);
    // style.css carries only the theme header; the design system is assets/css/main.css.
    wp_enqueue_style('sl-style', get_stylesheet_uri(), array(), $v);
    wp_enqueue_style('sl-main', SL_URL . '/assets/css/main.css', array('sl-style'), $v);
    wp_enqueue_script('sl-main', SL_URL . '/assets/js/main.js', array(), $v, true);
    wp_localize_script('sl-main', 'SPARKLIFE', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'sl_enqueue');

function sl_head_meta() {
    $icon = SL_URL . '/assets/img/logo.png';
    echo '<link rel="icon" type="image/png" href="' . esc_url($icon) . '">' . "\n";
    echo '<link rel="apple-touch-icon" href="' . esc_url($icon) . '">' . "\n";
    echo '<meta name="theme-color" content="#1567E3">' . "\n";
}
add_action('wp_head', 'sl_head_meta', 2);

/* ─── Google Analytics 4 ────────────────────────────────────────
 * Set the measurement ID in CC Fields → Global Variables (ga4_id).
 * Nothing is emitted until an ID is present, so staging stays clean.
 */
function sl_ga4() {
    $id = sl_get_var('ga4_id');
    if (!$id) return;
    echo "\n<!-- Google tag (gtag.js) -->\n";
    echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . esc_attr($id) . '"></script>' . "\n";
    echo "<script>\n";
    echo "  window.dataLayer = window.dataLayer || [];\n";
    echo "  function gtag(){dataLayer.push(arguments);}\n";
    echo "  gtag('js', new Date());\n";
    echo "  gtag('config', '" . esc_js($id) . "');\n";
    echo "</script>\n";
}
add_action('wp_head', 'sl_ga4', 1);

/* ─── Global variables ──────────────────────────────────────────
 * Reads CC Fields' ccf_global_vars, falling back to the theme defaults so the
 * site still renders correctly before the seeder has ever been run.
 */
function sl_default_vars() {
    return array(
        'company_name'     => 'Spark Life Electrical Contractors',
        'company_short'    => 'Spark Life',
        'company_phone'    => '0402 028 871',
        'company_tel'      => '0402028871',
        'company_email'    => 'info@spark-life.com.au',
        'company_address'  => '6 Magnolia Court, Frankston, VIC 3199',
        'company_suburb'   => 'Frankston',
        'company_region'   => 'Frankston, Bayside & the Mornington Peninsula',
        'company_hours'    => "Mon–Sat 7am–6pm\nSunday closed",
        'company_abn'      => '00 000 000 000',
        'rec_license'      => 'REC 27391',
        'owner_name'       => 'Liam',
        'owner_initials'   => 'LG',
        'owner_role'       => 'Owner & licensed electrician',
        'founded_year'     => '2018',
        // Blank until real Google reviews exist. Both the footer rating line and
        // the LocalBusiness aggregateRating only render when these are set, so
        // leaving them empty is what keeps unverified rating data off the site.
        'review_score'     => '',
        'review_count'     => '',
        'booking_url'      => '',
        'ga4_id'           => '',
        'facebook_url'     => '',
        'instagram_url'    => '',
        'google_reviews_url' => '',
    );
}

if (!function_exists('sl_get_var')) {
    function sl_get_var($key, $default = '') {
        if (function_exists('ccf_get_var')) {
            $v = ccf_get_var($key, null);
            if ($v !== null && $v !== '') return $v;
        }
        $opts = get_option('ccf_global_vars', array());
        if (is_array($opts) && isset($opts[$key]) && $opts[$key] !== '') return $opts[$key];
        $defs = sl_default_vars();
        if (isset($defs[$key]) && $defs[$key] !== '') return $defs[$key];
        return $default;
    }
}

/** tel: href from a display number ("0402 028 871" → "0402028871"). */
function sl_tel($number = '') {
    if (!$number) $number = sl_get_var('company_tel', sl_get_var('company_phone'));
    return preg_replace('/[^0-9+]/', '', $number);
}

/** AU phone in E.164 for schema (0402028871 → +61402028871). */
function sl_phone_e164() {
    $tel = preg_replace('/[^0-9]/', '', sl_get_var('company_tel', sl_get_var('company_phone')));
    if (strpos($tel, '0') === 0) $tel = '61' . substr($tel, 1);
    return $tel ? '+' . $tel : '';
}

/* ─── Sections plumbing ─────────────────────────────────────── */
function sl_has_sections($post_id = 0) {
    if (!$post_id) $post_id = get_the_ID();
    $s = get_post_meta($post_id, '_ccf_sections', true);
    return is_array($s) && count($s) > 0;
}

function sl_render_sections($post_id = 0) {
    if (!$post_id) $post_id = get_the_ID();
    if (function_exists('ccf_render_sections')) {
        ccf_render_sections($post_id);
        return;
    }
    echo '<div class="wrap" style="padding:80px 0"><p>' .
        esc_html__('The CC Fields plugin is not active, so page sections cannot be rendered.', 'sparklife') .
        '</p></div>';
}

/**
 * Render one section directly, without going through post meta.
 * Used by single-service.php to compose a sensible default page for a service
 * that hasn't been given its own sections yet.
 */
function sl_render_section($type, $data = array()) {
    $template = locate_template('cc-fields/sections/' . $type . '.php');
    if (!$template) return;
    $section_data   = class_exists('Ccf_Renderer') ? Ccf_Renderer::process_template_tags($data) : $data;
    $section_type   = $type;
    $section_config = array();
    include $template;
}

/* ─── Navigation ────────────────────────────────────────────────
 * Built in PHP (rather than wp_nav_menu) so the Services item can carry the
 * mega-menu of live Service posts. Editable labels/order live here.
 */
function sl_nav_items() {
    return apply_filters('sl_nav_items', array(
        array('label' => 'Home',     'url' => home_url('/')),
        array('label' => 'Services', 'url' => home_url('/services/'), 'services' => true),
        array('label' => 'About',    'url' => home_url('/about/')),
        array('label' => 'Projects', 'url' => home_url('/projects/')),
        array('label' => 'Contact',  'url' => home_url('/contact/')),
    ));
}

function sl_body_classes($classes) {
    $classes[] = 'sparklife';
    return $classes;
}
add_filter('body_class', 'sl_body_classes');

function sl_excerpt_more($more) { return '…'; }
add_filter('excerpt_more', 'sl_excerpt_more');

/* ─── Template helpers (shared by the section templates) ─────── */

/**
 * Wrap the highlighted words of a heading in the blue underline span.
 * If the needle isn't in the title it is appended, so editors can type either
 * "Power your home without the drama." + "drama" or just the trailing phrase.
 * Returns escaped HTML — never pass the result through esc_html() again.
 */
function sl_highlight($title, $needle = '') {
    $html = nl2br(esc_html((string) $title));
    $needle = trim((string) $needle);
    if ($needle === '') return $html;

    $esc  = esc_html($needle);
    $span = '<span class="hl__word">' . $esc . '</span>';
    $pos  = strpos($html, $esc);
    if ($pos !== false) {
        return substr_replace($html, $span, $pos, strlen($esc));
    }
    return trim($html . ' ' . $span);
}

/** Resolve a section URL: '/contact/' → home_url('/contact/'), absolute URLs untouched. */
function sl_link($url, $fallback = '') {
    $url = trim((string) $url);
    if ($url === '') $url = $fallback;
    if ($url === '') return '';
    if ($url[0] === '/' && (!isset($url[1]) || $url[1] !== '/')) return home_url($url);
    return $url;
}

/** The site-wide "call us" href. */
function sl_tel_href() {
    return 'tel:' . sl_tel();
}

/** Split a textarea of one-per-line values into a trimmed array. */
function sl_lines($text) {
    $out = array();
    foreach (preg_split('/\r\n|\r|\n/', (string) $text) as $line) {
        $line = trim($line);
        if ($line !== '') $out[] = $line;
    }
    return $out;
}

/**
 * Resolve a section image field: attachment ID first, then the *_url fallback.
 * Works whether or not CC Fields is active, so templates rendered directly by
 * the theme (single-service.php) never depend on the plugin being loaded.
 */
function sl_image($data, $key = 'image', $size = 'large') {
    $id = isset($data[$key]) ? $data[$key] : '';
    if ($id !== '' && is_numeric($id)) {
        $url = wp_get_attachment_image_url((int) $id, $size);
        if ($url) return $url;
    }
    $fallback = $key . '_url';
    return isset($data[$fallback]) ? (string) $data[$fallback] : '';
}

/** Read a section field with a default, tolerating missing keys. */
function sl_field($data, $key, $default = '') {
    return (isset($data[$key]) && $data[$key] !== '') ? $data[$key] : $default;
}

/** Truthiness for CC Fields toggle values ('1' / 1 / true). */
function sl_on($value, $default = false) {
    if ($value === null || $value === '') return $default;
    return in_array($value, array('1', 1, true, 'true', 'yes'), true);
}

/* ─── Includes ──────────────────────────────────────────────── */
require_once SL_PATH . '/inc/icons.php';
require_once SL_PATH . '/inc/cpt-service.php';
require_once SL_PATH . '/inc/sections.php';
require_once SL_PATH . '/inc/seo.php';
require_once SL_PATH . '/inc/seed-content.php';
