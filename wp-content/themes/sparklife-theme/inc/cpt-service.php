<?php
/**
 * Services custom post type.
 *
 * Every service the business offers is a `service` post living at
 * /services/{slug}/. The Services landing page is a normal WordPress page at
 * /services/ (built from CC Fields sections), so `has_archive` is off and the
 * page owns that URL — services_grid sections query this CPT to list them.
 *
 * Removing a service is simply trashing (or unpublishing) its post: it drops out
 * of the nav mega-menu, the footer, the home bento grid, the services grid and
 * the enquiry-form dropdowns automatically.
 *
 * Per-service extras live in a small native meta box (icon, tagline, price-from,
 * feature flags). Long-form page content is CC Fields sections, same as pages.
 */
if (!defined('ABSPATH')) exit;

/* ─── Registration ──────────────────────────────────────────── */
function sl_register_service_cpt() {
    register_post_type('service', array(
        'labels' => array(
            'name'               => __('Services', 'sparklife'),
            'singular_name'      => __('Service', 'sparklife'),
            'add_new'            => __('Add Service', 'sparklife'),
            'add_new_item'       => __('Add New Service', 'sparklife'),
            'edit_item'          => __('Edit Service', 'sparklife'),
            'new_item'           => __('New Service', 'sparklife'),
            'view_item'          => __('View Service', 'sparklife'),
            'search_items'       => __('Search Services', 'sparklife'),
            'not_found'          => __('No services yet', 'sparklife'),
            'not_found_in_trash' => __('No services in the bin', 'sparklife'),
            'all_items'          => __('All Services', 'sparklife'),
            'menu_name'          => __('Services', 'sparklife'),
        ),
        'public'        => true,
        'has_archive'   => false, // the /services/ page owns that URL
        'menu_icon'     => 'dashicons-lightbulb',
        'menu_position' => 21,
        'supports'      => array('title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields', 'revisions'),
        'rewrite'       => array('slug' => 'services', 'with_front' => false),
        'show_in_rest'  => true, // required for the MyMomo connector's REST bridge
        'hierarchical'  => false,
    ));

    register_taxonomy('service_category', 'service', array(
        'labels' => array(
            'name'          => __('Service Categories', 'sparklife'),
            'singular_name' => __('Service Category', 'sparklife'),
            'menu_name'     => __('Categories', 'sparklife'),
        ),
        'public'            => true,
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => array('slug' => 'service-category', 'with_front' => false),
    ));
}
add_action('init', 'sl_register_service_cpt');

/** Services are ordered by menu_order in the admin list, like pages. */
function sl_service_admin_order($query) {
    if (!is_admin() || !$query->is_main_query()) return;
    if ($query->get('post_type') === 'service' && !$query->get('orderby')) {
        $query->set('orderby', 'menu_order title');
        $query->set('order', 'ASC');
    }
}
add_action('pre_get_posts', 'sl_service_admin_order');

/* ─── Query helper ──────────────────────────────────────────────
 * The single source of truth for "what services do we offer?". Everything that
 * lists services (nav, footer, bento grid, service grids, form dropdowns) goes
 * through here, so unpublishing a service removes it everywhere at once.
 *
 * @param array $args  limit  int    max services (0 = all)
 *                     featured bool only services flagged "feature on home"
 *                     exclude int   post ID to leave out (e.g. the current one)
 * @return array[] { id, title, url, excerpt, icon, tagline, price_from, featured, accent, image }
 */
function sl_get_services($args = array()) {
    $args = wp_parse_args($args, array(
        'limit'    => 0,
        'featured' => false,
        'exclude'  => 0,
        'category' => '',
    ));

    $q = array(
        'post_type'      => 'service',
        'post_status'    => 'publish',
        'posts_per_page' => $args['limit'] > 0 ? (int) $args['limit'] : -1,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    );
    if ($args['exclude']) $q['post__not_in'] = array((int) $args['exclude']);
    if ($args['featured']) {
        $q['meta_query'] = array(array('key' => '_sl_service_featured', 'value' => '1'));
    }
    if ($args['category']) {
        $q['tax_query'] = array(array('taxonomy' => 'service_category', 'field' => 'slug', 'terms' => $args['category']));
    }

    $out = array();
    foreach (get_posts($q) as $p) {
        $out[] = array(
            'id'         => $p->ID,
            'title'      => get_the_title($p),
            'url'        => get_permalink($p),
            'excerpt'    => sl_service_meta($p->ID, 'summary') ?: (string) $p->post_excerpt,
            'icon'       => sl_service_meta($p->ID, 'icon') ?: sl_service_icon_for(get_the_title($p)),
            'tagline'    => sl_service_meta($p->ID, 'tagline'),
            'price_from' => sl_service_meta($p->ID, 'price_from'),
            'featured'   => sl_service_meta($p->ID, 'featured') === '1',
            'accent'     => sl_service_meta($p->ID, 'accent') === '1',
            'image'      => get_the_post_thumbnail_url($p->ID, 'large') ?: sl_service_meta($p->ID, 'image_url'),
        );
    }
    return $out;
}

/** Read one of the service meta fields (stored as _sl_service_{key}). */
function sl_service_meta($post_id, $key) {
    return (string) get_post_meta($post_id, '_sl_service_' . $key, true);
}

/** Service titles for the enquiry-form "What do you need?" dropdown. */
function sl_service_options() {
    $names = wp_list_pluck(sl_get_services(), 'title');
    $names[] = __('Something else', 'sparklife');
    return $names;
}

/* ─── Meta box ──────────────────────────────────────────────── */
function sl_service_meta_fields() {
    return array(
        'tagline'    => array('label' => __('Tagline', 'sparklife'), 'type' => 'text',
                              'desc'  => __('Short line under the title on the service hero, e.g. "Safety switches that actually protect your family."', 'sparklife')),
        'summary'    => array('label' => __('Card summary', 'sparklife'), 'type' => 'textarea',
                              'desc'  => __('One or two sentences shown on service cards and grids. Falls back to the excerpt.', 'sparklife')),
        'icon'       => array('label' => __('Icon', 'sparklife'), 'type' => 'select', 'options' => 'icons',
                              'desc'  => __('Shown on cards and in the Services mega-menu.', 'sparklife')),
        'price_from' => array('label' => __('Price from', 'sparklife'), 'type' => 'text',
                              'desc'  => __('Optional, e.g. "$149". Leave blank to hide.', 'sparklife')),
        'image_url'  => array('label' => __('Image URL', 'sparklife'), 'type' => 'text',
                              'desc'  => __('Optional fallback when no featured image is set.', 'sparklife')),
        'badge'      => array('label' => __('Card badge', 'sparklife'), 'type' => 'text',
                              'desc'  => __('Small pill on the card, e.g. "Most booked ⚡".', 'sparklife')),
        'featured'   => array('label' => __('Feature on the home page', 'sparklife'), 'type' => 'checkbox',
                              'desc'  => __('Renders as the large dark card in the bento grid.', 'sparklife')),
        'accent'     => array('label' => __('Accent card (blue)', 'sparklife'), 'type' => 'checkbox',
                              'desc'  => __('Renders as the blue highlight card in the bento grid.', 'sparklife')),
    );
}

function sl_service_add_meta_box() {
    add_meta_box('sl_service_details', __('Service details', 'sparklife'), 'sl_service_meta_box', 'service', 'side', 'high');
}
add_action('add_meta_boxes', 'sl_service_add_meta_box');

function sl_service_meta_box($post) {
    wp_nonce_field('sl_service_meta', 'sl_service_meta_nonce');
    echo '<style>.sl-mb p{margin:0 0 14px}.sl-mb label{font-weight:600;display:block;margin-bottom:4px}.sl-mb .description{font-weight:400;margin-top:4px}</style>';
    echo '<div class="sl-mb">';
    foreach (sl_service_meta_fields() as $key => $f) {
        $name  = '_sl_service_' . $key;
        $value = get_post_meta($post->ID, $name, true);
        echo '<p><label for="' . esc_attr($name) . '">' . esc_html($f['label']) . '</label>';
        if ($f['type'] === 'textarea') {
            echo '<textarea class="widefat" rows="3" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '">' . esc_textarea($value) . '</textarea>';
        } elseif ($f['type'] === 'checkbox') {
            echo '<input type="hidden" name="' . esc_attr($name) . '" value="0">';
            echo '<input type="checkbox" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="1" ' . checked($value, '1', false) . '>';
        } elseif ($f['type'] === 'select' && $f['options'] === 'icons') {
            echo '<select class="widefat" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '">';
            echo '<option value="">' . esc_html__('Auto (from the title)', 'sparklife') . '</option>';
            foreach (array_keys(sl_icon_names()) as $icon) {
                echo '<option value="' . esc_attr($icon) . '" ' . selected($value, $icon, false) . '>' . esc_html($icon) . '</option>';
            }
            echo '</select>';
        } else {
            echo '<input type="text" class="widefat" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
        }
        if (!empty($f['desc'])) echo '<span class="description">' . esc_html($f['desc']) . '</span>';
        echo '</p>';
    }
    echo '</div>';
}

function sl_service_save_meta($post_id) {
    if (!isset($_POST['sl_service_meta_nonce']) || !wp_verify_nonce($_POST['sl_service_meta_nonce'], 'sl_service_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    foreach (sl_service_meta_fields() as $key => $f) {
        $name = '_sl_service_' . $key;
        if (!isset($_POST[$name])) continue;
        $raw = wp_unslash($_POST[$name]);
        $val = ($f['type'] === 'textarea') ? sanitize_textarea_field($raw) : sanitize_text_field($raw);
        // Unticked checkboxes post "0" (via the hidden input) — store nothing.
        $is_empty = ($val === '') || ($f['type'] === 'checkbox' && $val !== '1');
        if ($is_empty) {
            delete_post_meta($post_id, $name);
        } else {
            update_post_meta($post_id, $name, $val);
        }
    }
}
add_action('save_post_service', 'sl_service_save_meta');

/* ─── Admin list column ─────────────────────────────────────── */
function sl_service_columns($cols) {
    $new = array();
    foreach ($cols as $k => $v) {
        $new[$k] = $v;
        if ($k === 'title') {
            $new['sl_tagline'] = __('Tagline', 'sparklife');
            $new['sl_flags']   = __('Home page', 'sparklife');
        }
    }
    return $new;
}
add_filter('manage_service_posts_columns', 'sl_service_columns');

function sl_service_column_content($col, $post_id) {
    if ($col === 'sl_tagline') {
        echo esc_html(sl_service_meta($post_id, 'tagline'));
    } elseif ($col === 'sl_flags') {
        $flags = array();
        if (sl_service_meta($post_id, 'featured') === '1') $flags[] = __('Featured', 'sparklife');
        if (sl_service_meta($post_id, 'accent') === '1')   $flags[] = __('Accent', 'sparklife');
        echo esc_html($flags ? implode(' · ', $flags) : '—');
    }
}
add_action('manage_service_posts_custom_column', 'sl_service_column_content', 10, 2);
