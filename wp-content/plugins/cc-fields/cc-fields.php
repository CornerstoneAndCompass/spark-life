<?php
/**
 * Plugin Name:       CC Fields
 * Plugin URI:        https://cornerstoneandcompass.com/plugins/cc-fields
 * Description:       Lightweight page-section builder and global site variables — Cornerstone & Compass's in-house alternative to ACF. Section types are defined per-site in includes/class-cc-sections.php; page content is seeded from the active theme via the ccf_seed_pages filter.
 * Version:           1.1.2
 * Author:            Cornerstone & Compass
 * Author URI:        https://cornerstoneandcompass.com
 * License:           GPL v2 or later
 * Text Domain:       cc-fields
 */

if (!defined('ABSPATH')) exit;

define('CCF_VERSION', '1.1.2');
define('CCF_PATH', plugin_dir_path(__FILE__));
define('CCF_URL', plugin_dir_url(__FILE__));

require_once CCF_PATH . 'includes/class-cc-fields-core.php';
require_once CCF_PATH . 'includes/class-cc-sections.php';
require_once CCF_PATH . 'includes/class-cc-global-vars.php';
require_once CCF_PATH . 'includes/class-cc-renderer.php';
require_once CCF_PATH . 'includes/class-cc-migration.php';

function ccf_init() {
    (new Ccf_Fields_Core())->init();
    (new Ccf_Sections())->init();
    (new Ccf_Global_Vars())->init();
    (new Ccf_Renderer())->init();
    (new Ccf_Migration())->init();
}
add_action('plugins_loaded', 'ccf_init');

/**
 * Default global variables. A site can override any of these by seeding the
 * ccf_global_vars option (the seeder does this from the theme), or by editing
 * them under CC Fields → Global Variables. Defaults below are intentionally
 * generic so the plugin carries no client-specific content.
 */
function ccf_default_vars() {
    return apply_filters('ccf_default_vars', array(
        'company_name'  => get_bloginfo('name'),
        'company_phone' => '',
        'company_email' => get_option('admin_email'),
        'company_address' => '',
        'company_abn'   => '',
        'company_hours' => '',
        'facebook_url'  => '',
        'instagram_url' => '',
        'youtube_url'   => '',
        'linkedin_url'  => '',
    ));
}

register_activation_hook(__FILE__, function () {
    if (!get_option('ccf_global_vars')) {
        update_option('ccf_global_vars', ccf_default_vars());
    }
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

/* ─── Front-end enquiry form handler ─────────────────────────────
 * Used by the hero / contact section templates. Posts to admin-ajax with
 * action=ccf_submit_form. Emails the company address (ccf_get_var) and stores
 * a copy on the submitted page so there is always a record.
 */
add_action('wp_ajax_ccf_submit_form',        'ccf_handle_form_submit');
add_action('wp_ajax_nopriv_ccf_submit_form', 'ccf_handle_form_submit');

function ccf_handle_form_submit() {
    if (!wp_verify_nonce(isset($_POST['ccf_form_nonce']) ? $_POST['ccf_form_nonce'] : '', 'ccf_form_submit')) {
        wp_send_json_error(array('message' => 'Security check failed. Please refresh and try again.'), 400);
    }
    if (!empty($_POST['ccf_hp'])) { // honeypot
        wp_send_json_success(array('message' => 'Thanks, we received your message.'));
    }

    $skip = array('action', 'ccf_form_nonce', 'ccf_hp', 'page_id');
    $fields = array();
    foreach ($_POST as $k => $v) {
        if (in_array($k, $skip, true)) continue;
        if (is_array($v)) {
            $fields[$k] = array_map('sanitize_text_field', wp_unslash($v));
        } else {
            $raw = wp_unslash((string) $v);
            $fields[$k] = (strpos($raw, "\n") !== false) ? sanitize_textarea_field($raw) : sanitize_text_field($raw);
        }
    }

    if (empty($fields['email']) && empty($fields['phone'])) {
        wp_send_json_error(array('message' => 'Please provide a phone number or email so we can reach you.'), 422);
    }
    if (!empty($fields['email']) && !is_email($fields['email'])) {
        wp_send_json_error(array('message' => 'That email address does not look right. Please double-check it.'), 422);
    }

    $to = function_exists('ccf_get_var') ? ccf_get_var('company_email', '') : '';
    if (!$to) $to = get_option('admin_email');

    $label_map = array(
        'name' => 'Name', 'first_name' => 'First name', 'last_name' => 'Last name',
        'phone' => 'Phone', 'email' => 'Email', 'suburb' => 'Suburb',
        'service' => 'Service required', 'service_required' => 'Service required',
        'message' => 'Message',
    );

    $from_name = !empty($fields['name']) ? $fields['name']
        : trim((isset($fields['first_name']) ? $fields['first_name'] : '') . ' ' . (isset($fields['last_name']) ? $fields['last_name'] : ''));
    if (!$from_name) $from_name = !empty($fields['email']) ? $fields['email'] : 'Website enquiry';
    $service = !empty($fields['service']) ? $fields['service'] : (!empty($fields['service_required']) ? $fields['service_required'] : 'Enquiry');
    $site = function_exists('ccf_get_var') ? ccf_get_var('company_name', get_bloginfo('name')) : get_bloginfo('name');
    $subject = sprintf('[%s] %s from %s', $site, $service, $from_name);

    $lines = array('New enquiry from the ' . $site . ' website.', '');
    foreach ($fields as $k => $v) {
        if ($v === '' || $v === null) continue;
        $label = isset($label_map[$k]) ? $label_map[$k] : ucwords(str_replace('_', ' ', $k));
        $lines[] = $label . ': ' . (is_array($v) ? implode(', ', $v) : $v);
    }
    $page_id = isset($_POST['page_id']) ? (int) $_POST['page_id'] : 0;
    if ($page_id) { $lines[] = ''; $lines[] = 'Submitted from: ' . get_permalink($page_id); }
    $body = implode("\n", $lines);

    $headers = array('Content-Type: text/plain; charset=UTF-8');
    if (!empty($fields['email'])) $headers[] = 'Reply-To: ' . $from_name . ' <' . $fields['email'] . '>';

    $sent = wp_mail($to, $subject, $body, $headers);

    if ($page_id) {
        $subs = get_post_meta($page_id, '_ccf_form_submissions', true);
        if (!is_array($subs)) $subs = array();
        array_unshift($subs, array('ts' => current_time('mysql'), 'fields' => $fields, 'sent' => (bool) $sent, 'to' => $to));
        update_post_meta($page_id, '_ccf_form_submissions', array_slice($subs, 0, 200));
    }

    if (!$sent) {
        wp_send_json_error(array('message' => 'Sorry, we could not send your message right now. Please call us instead.'), 500);
    }
    wp_send_json_success(array('message' => 'Thanks, your message has been sent. We will be in touch shortly.'));
}


// ── Auto-updates from MyMomo ────────────────────────────────────────────────────
// Same private update channel as the VOA connector: the MyMomo worker
// hosts versioned zips, WordPress polls /check and shows the normal plugin
// update flow when a newer version is published. Update once centrally and
// every client site gets the update notice.
define('CCF_UPDATE_URL', 'https://api.virtualofficeai.com.au/api/plugin-update');
define('CCF_PLUGIN_SLUG', 'cc-fields');

add_filter('pre_set_site_transient_update_plugins', 'ccf_check_for_updates');
function ccf_check_for_updates($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }
    $plugin_file = CCF_PLUGIN_SLUG . '/cc-fields.php';
    $response = wp_remote_get(add_query_arg(array(
        'version' => CCF_VERSION,
        'slug'    => CCF_PLUGIN_SLUG,
        'site'    => rawurlencode((string) wp_parse_url(home_url(), PHP_URL_HOST)),
    ), CCF_UPDATE_URL . '/check'), array('timeout' => 10));
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return $transient;
    }
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!empty($data['new_version']) && version_compare(CCF_VERSION, $data['new_version'], '<')) {
        $transient->response[$plugin_file] = (object) array(
            'slug'        => CCF_PLUGIN_SLUG,
            'plugin'      => $plugin_file,
            'new_version' => $data['new_version'],
            'package'     => $data['download_url'],
            'url'         => $data['homepage'] ?? 'https://virtualofficeai.com.au',
            'tested'      => $data['tested'] ?? '6.7',
            'requires'    => $data['requires'] ?? '5.8',
        );
    }
    return $transient;
}

add_filter('plugins_api', 'ccf_plugin_info', 20, 3);
function ccf_plugin_info($result, $action, $args) {
    if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== CCF_PLUGIN_SLUG) {
        return $result;
    }
    $response = wp_remote_get(add_query_arg(array('slug' => CCF_PLUGIN_SLUG), CCF_UPDATE_URL . '/info'), array('timeout' => 10));
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return $result;
    }
    $info = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($info)) {
        return $result;
    }
    return (object) array(
        'name'          => $info['name'] ?? 'CC Fields',
        'slug'          => CCF_PLUGIN_SLUG,
        'version'       => $info['version'] ?? CCF_VERSION,
        'author'        => $info['author'] ?? 'Cornerstone & Compass',
        'homepage'      => $info['homepage'] ?? 'https://virtualofficeai.com.au',
        'download_link' => $info['download_url'] ?? '',
        'requires'      => $info['requires'] ?? '5.8',
        'tested'        => $info['tested'] ?? '6.7',
        'sections'      => array(
            'description' => $info['description'] ?? 'Lightweight page-section builder and global site variables.',
            'changelog'   => $info['changelog'] ?? '',
        ),
    );
}
