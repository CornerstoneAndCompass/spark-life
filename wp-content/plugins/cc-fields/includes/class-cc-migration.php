<?php
/**
 * CC Fields — content seeder.
 *
 * Generic and content-agnostic: the active theme supplies the pages, global
 * variables and front-page slug via filters, and this class creates/updates the
 * WordPress pages and their _ccf_sections meta. Running it again is idempotent
 * (pages are matched by slug + parent and updated in place).
 *
 * Theme provides:
 *   add_filter('ccf_seed_vars',  fn() => [ 'company_phone' => '…', … ]);
 *   add_filter('ccf_seed_pages', fn() => [ [ 'slug'=>'home','title'=>'…','parent'=>null,
 *                                            'excerpt'=>'','menu_order'=>0,
 *                                            'sections'=>[ ['type'=>'hero','data'=>[…]], … ] ], … ]);
 *   add_filter('ccf_seed_front', fn() => 'home');
 */
if (!defined('ABSPATH')) exit;

class Ccf_Migration {

    public function init() {
        add_action('admin_menu', array($this, 'menu'), 20);
        add_action('admin_post_ccf_run_seed', array($this, 'handle_post'));
        add_action('wp_ajax_ccf_seed', array($this, 'handle_ajax'));
    }

    public function menu() {
        add_submenu_page('cc-fields', 'Seed / Rebuild Content', 'Seed / Rebuild', 'manage_options', 'cc-fields-seed', array($this, 'page'));
    }

    public function page() {
        $report = get_transient('ccf_seed_report');
        if ($report) delete_transient('ccf_seed_report');
        ?>
        <div class="wrap">
            <h1>Seed / Rebuild Content</h1>
            <p>Creates or updates every page defined by the active theme (their sections, titles, excerpts and parents) and sets the static front page. Safe to run repeatedly — existing pages are updated in place, not duplicated.</p>
            <?php if ($report) : ?>
                <div class="notice notice-success"><p>
                    Seeded <strong><?php echo (int) count($report['pages']); ?></strong> pages
                    (<?php echo esc_html(implode(', ', $report['pages'])); ?>);
                    updated <strong><?php echo (int) $report['vars']; ?></strong> global variables.
                    Front page: <strong><?php echo esc_html($report['front']); ?></strong>.
                </p></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="ccf_run_seed">
                <?php wp_nonce_field('ccf_seed', 'ccf_seed_nonce'); ?>
                <p><button type="submit" class="button button-primary button-hero">Seed / Rebuild content</button></p>
            </form>
        </div>
        <?php
    }

    public function handle_post() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        if (!isset($_POST['ccf_seed_nonce']) || !wp_verify_nonce($_POST['ccf_seed_nonce'], 'ccf_seed')) wp_die('Bad nonce');
        $report = $this->seed();
        set_transient('ccf_seed_report', $report, 60);
        wp_safe_redirect(admin_url('admin.php?page=cc-fields-seed'));
        exit;
    }

    /** URL-triggerable while authenticated: /wp-admin/admin-ajax.php?action=ccf_seed */
    public function handle_ajax() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized', 403);
        wp_send_json_success($this->seed());
    }

    public function seed() {
        $report = array('vars' => 0, 'pages' => array(), 'front' => '');

        // 1) Global variables
        $vars = apply_filters('ccf_seed_vars', array());
        if (is_array($vars) && $vars) {
            $cur = get_option('ccf_global_vars', array());
            if (!is_array($cur)) $cur = array();
            update_option('ccf_global_vars', array_merge($cur, $vars));
            $report['vars'] = count($vars);
        }

        // 2) Pages (parents before children)
        $defs = apply_filters('ccf_seed_pages', array());
        if (!is_array($defs)) $defs = array();
        usort($defs, function ($a, $b) {
            return (empty($a['parent']) ? 0 : 1) - (empty($b['parent']) ? 0 : 1);
        });

        $id_by_slug = array();
        foreach ($defs as $d) {
            if (empty($d['slug'])) continue;
            $slug = sanitize_title($d['slug']);
            // Definitions may target any post type (e.g. a 'service' CPT); pages
            // remain the default so existing themes are unaffected.
            $post_type = !empty($d['post_type']) ? sanitize_key($d['post_type']) : 'page';
            $parent_id = 0;
            if (!empty($d['parent'])) {
                $pslug = sanitize_title($d['parent']);
                $parent_id = isset($id_by_slug[$pslug]) ? $id_by_slug[$pslug] : $this->find_page($pslug, 0, $post_type);
            }
            $existing = $this->find_page($slug, $parent_id, $post_type);
            $postarr = array(
                'post_type'    => $post_type,
                'post_title'   => isset($d['title']) ? $d['title'] : ucwords(str_replace('-', ' ', $slug)),
                'post_name'    => $slug,
                'post_status'  => 'publish',
                'post_parent'  => $parent_id,
                'post_excerpt' => isset($d['excerpt']) ? $d['excerpt'] : '',
                'menu_order'   => isset($d['menu_order']) ? (int) $d['menu_order'] : 0,
            );
            if ($existing) {
                $postarr['ID'] = $existing;
                $pid = wp_update_post($postarr, true);
            } else {
                $postarr['post_content'] = '';
                $pid = wp_insert_post($postarr, true);
            }
            if (!$pid || is_wp_error($pid)) continue;
            update_post_meta($pid, '_ccf_sections', $this->clean_sections(isset($d['sections']) ? $d['sections'] : array()));

            // Per-page SEO (title / desc / og_image) → _ccf_seo_* meta, read by the theme.
            if (!empty($d['seo']) && is_array($d['seo'])) {
                foreach ($d['seo'] as $sk => $sv) {
                    update_post_meta($pid, '_ccf_seo_' . sanitize_key($sk), (string) $sv);
                }
            }

            // Arbitrary extra post meta (e.g. a service's icon or tagline).
            if (!empty($d['meta']) && is_array($d['meta'])) {
                foreach ($d['meta'] as $mk => $mv) {
                    update_post_meta($pid, sanitize_key($mk), is_array($mv) ? $mv : (string) $mv);
                }
            }

            // Taxonomy terms, keyed by taxonomy: [ 'service_category' => ['Residential'] ].
            if (!empty($d['terms']) && is_array($d['terms'])) {
                foreach ($d['terms'] as $tax => $terms) {
                    if (taxonomy_exists($tax)) {
                        wp_set_object_terms($pid, (array) $terms, $tax, false);
                    }
                }
            }

            $id_by_slug[$slug] = $pid;
            $report['pages'][] = $slug;
        }

        // 3) Static front page
        $front = sanitize_title(apply_filters('ccf_seed_front', 'home'));
        if (isset($id_by_slug[$front])) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $id_by_slug[$front]);
            $report['front'] = $front;
        }

        // 4) Pretty permalinks with a trailing slash (preserve the old-site URL shape,
        //    e.g. /plumbing-services/hot-water-systems/).
        $permalink = apply_filters('ccf_seed_permalink', '/%postname%/');
        if ($permalink && get_option('permalink_structure') !== $permalink) {
            update_option('permalink_structure', $permalink);
        }

        flush_rewrite_rules();
        return $report;
    }

    private function find_page($slug, $parent_id = 0, $post_type = 'page') {
        $args = array(
            'post_type'   => $post_type,
            'name'        => $slug,
            'post_status' => 'any',
            'numberposts' => 1,
            'fields'      => 'ids',
        );
        // Only hierarchical types have a meaningful parent; constraining a flat
        // CPT to post_parent = 0 is harmless but pointless, so skip it.
        if (is_post_type_hierarchical($post_type)) {
            $args['post_parent'] = $parent_id;
        }
        $q = get_posts($args);
        return $q ? (int) $q[0] : 0;
    }

    private function clean_sections($sections) {
        $clean = array();
        if (!is_array($sections)) return $clean;
        foreach ($sections as $s) {
            if (empty($s['type'])) continue;
            $clean[] = array(
                'type' => sanitize_key($s['type']),
                'data' => (isset($s['data']) && is_array($s['data'])) ? $s['data'] : array(),
            );
        }
        return $clean;
    }
}
