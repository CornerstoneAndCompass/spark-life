<?php
/**
 * SEO — per-page title/description/canonical, Open Graph, Twitter cards and
 * JSON-LD (Electrician/LocalBusiness + WebSite + WebPage + Breadcrumb).
 *
 * Sources, in priority order:
 *   1. Yoast / Rank Math, if either is ever installed — we stand down entirely.
 *   2. The MyMomo connector's own SEO meta (_voa_seo_*), which it renders itself
 *      — we stand down per-tag so nothing is emitted twice.
 *   3. _ccf_seo_* post meta, seeded from data/content.json by the CC Fields
 *      seeder and editable per page.
 *   4. Sensible fallbacks (page excerpt, site tagline).
 *
 * Robots (index/noindex) is left to WordPress core, so the "discourage search
 * engines" switch keeps staging out of Google.
 */
if (!defined('ABSPATH')) exit;

/** A third-party SEO plugin owns the head. */
function sl_seo_plugin_active() {
    return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || class_exists('RankMath');
}

/** The MyMomo connector has SEO data for this post and will render it itself. */
function sl_seo_connector_owns($post_id = 0) {
    if (!function_exists('voa_seo_output_head')) return false;
    if (!$post_id) $post_id = get_queried_object_id();
    if (!$post_id) return false;
    foreach (array('_voa_seo_desc', '_voa_seo_canonical', '_voa_seo_og_title', '_voa_seo_jsonld') as $key) {
        if (get_post_meta($post_id, $key, true)) return true;
    }
    return false;
}

/** Read a seeded SEO value for a post. */
function sl_seo($key, $post_id = 0) {
    if (!$post_id) $post_id = get_queried_object_id();
    return $post_id ? (string) get_post_meta($post_id, '_ccf_seo_' . $key, true) : '';
}

/* ─── Document title ────────────────────────────────────────── */
add_filter('pre_get_document_title', function ($title) {
    if (sl_seo_plugin_active()) return $title;
    if (is_singular()) {
        $t = sl_seo('title');
        if ($t !== '') return $t;
    }
    return $title;
}, 20);

/* We emit our own canonical, so drop core's to avoid a duplicate. */
add_action('template_redirect', function () {
    if (!sl_seo_plugin_active() && !sl_seo_connector_owns()) {
        remove_action('wp_head', 'rel_canonical');
    }
});

/* ─── Head output ───────────────────────────────────────────── */
add_action('wp_head', 'sl_seo_head', 2);
function sl_seo_head() {
    if (sl_seo_plugin_active()) return;

    $singular  = is_singular();
    $pid       = get_queried_object_id();
    $connector = sl_seo_connector_owns($pid);

    $name  = sl_get_var('company_name', get_bloginfo('name'));
    $home  = home_url('/');
    // Canonical. Falling back to the home URL for everything non-singular was
    // wrong: it told Google every term archive was a duplicate of the home page,
    // so those URLs were never indexed. Term archives are self-referencing now,
    // and anything genuinely without a stable URL (search, 404) still falls back.
    $url = $home;
    if ($singular && !is_front_page()) {
        $url = get_permalink($pid);
    } elseif (is_tax() || is_category() || is_tag()) {
        $term_link = get_term_link(get_queried_object());
        if (!is_wp_error($term_link)) {
            $url = $term_link;
        }
    }
    $title = wp_get_document_title();

    $desc = $singular ? sl_seo('desc') : '';
    if ($desc === '' && $singular) {
        $desc = wp_strip_all_tags(get_the_excerpt($pid));
    }
    if ($desc === '' && (is_tax() || is_category() || is_tag())) {
        $desc = wp_strip_all_tags(term_description());
    }
    if ($desc === '') {
        $desc = get_bloginfo('description');
    }

    $img = $singular ? sl_seo('og_image') : '';
    if (!$img && $singular && has_post_thumbnail($pid)) {
        $img = get_the_post_thumbnail_url($pid, 'large');
    }
    if (!$img) $img = sl_logo_url();

    if (!$connector) {
        if ($desc) echo '<meta name="description" content="' . esc_attr($desc) . '" />' . "\n";
        echo '<link rel="canonical" href="' . esc_url($url) . '" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
        if ($desc) echo '<meta property="og:description" content="' . esc_attr($desc) . '" />' . "\n";
        echo '<meta property="og:type" content="' . (is_front_page() ? 'website' : 'article') . '" />' . "\n";
        echo '<meta property="og:image" content="' . esc_url($img) . '" />' . "\n";
    }
    // These are never emitted by the connector, so they are always safe to add.
    echo '<meta property="og:locale" content="en_AU" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($name) . '" />' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
    if ($desc) echo '<meta name="twitter:description" content="' . esc_attr($desc) . '" />' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url($img) . '" />' . "\n";

    // ── JSON-LD graph ─────────────────────────────────────────
    $graph = array();

    $business = array(
        '@type'      => array('Electrician', 'LocalBusiness'),
        '@id'        => $home . '#business',
        'name'       => $name,
        'url'        => $home,
        'telephone'  => sl_phone_e164(),
        'email'      => sl_get_var('company_email'),
        'image'      => sl_logo_url(),
        'logo'       => sl_logo_url(),
        'priceRange' => '$$',
        'areaServed' => array_values(array_filter(array_map(function ($s) {
            return array('@type' => 'City', 'name' => $s);
        }, sl_lines(sl_get_var('service_area_list'))))),
        'address'    => array(
            '@type'           => 'PostalAddress',
            'streetAddress'   => sl_get_var('company_address'),
            'addressLocality' => sl_get_var('company_suburb', 'Frankston'),
            'addressRegion'   => 'VIC',
            'addressCountry'  => 'AU',
        ),
        'sameAs' => array_values(array_filter(array(
            sl_get_var('facebook_url'),
            sl_get_var('instagram_url'),
            sl_get_var('google_reviews_url'),
        ))),
    );
    if (!$business['areaServed']) {
        $business['areaServed'] = array(array('@type' => 'City', 'name' => sl_get_var('company_suburb', 'Frankston')));
    }
    if ($rec = sl_get_var('rec_license')) {
        $business['identifier'] = $rec;
    }
    if (($score = sl_get_var('review_score')) && ($count = sl_get_var('review_count'))) {
        $business['aggregateRating'] = array(
            '@type'       => 'AggregateRating',
            'ratingValue' => $score,
            'reviewCount' => preg_replace('/[^0-9]/', '', $count),
            'bestRating'  => '5',
        );
    }
    $graph[] = $business;

    $graph[] = array(
        '@type' => 'WebSite', '@id' => $home . '#website', 'url' => $home,
        'name' => $name, 'publisher' => array('@id' => $home . '#business'), 'inLanguage' => 'en-AU',
    );

    // Service pages describe the service they sell.
    if (is_singular('service')) {
        $graph[] = array(
            '@type'       => 'Service',
            '@id'         => $url . '#service',
            'name'        => get_the_title($pid),
            'description' => $desc,
            'serviceType' => get_the_title($pid),
            'provider'    => array('@id' => $home . '#business'),
            'areaServed'  => $business['areaServed'],
            'url'         => $url,
        );
    }

    $graph[] = array(
        '@type' => 'WebPage', '@id' => $url . '#webpage', 'url' => $url,
        'name' => $title, 'description' => $desc,
        'isPartOf' => array('@id' => $home . '#website'), 'inLanguage' => 'en-AU',
    );

    $crumbs = array(array('@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $home));
    if ($singular && !is_front_page()) {
        $pos = 2;
        if (is_singular('service')) {
            $crumbs[] = array('@type' => 'ListItem', 'position' => $pos++, 'name' => 'Services', 'item' => home_url('/services/'));
        } else {
            $ancestors = array();
            $walk = $pid;
            while ($walk) {
                $parent = wp_get_post_parent_id($walk);
                if (!$parent) break;
                array_unshift($ancestors, $parent);
                $walk = $parent;
            }
            foreach ($ancestors as $a) {
                $crumbs[] = array('@type' => 'ListItem', 'position' => $pos++, 'name' => get_the_title($a), 'item' => get_permalink($a));
            }
        }
        $crumbs[] = array('@type' => 'ListItem', 'position' => $pos, 'name' => get_the_title($pid), 'item' => $url);
    }
    $graph[] = array('@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $crumbs);

    echo '<script type="application/ld+json">' .
        wp_json_encode(array('@context' => 'https://schema.org', '@graph' => $graph), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) .
        '</script>' . "\n";
}

/* ─── Sitemap: only advertise indexable URLs ────────────────────────
 * The MyMomo connector noindexes every taxonomy archive (voa_noindex_thin_archives),
 * treating them as thin listing pages, but WordPress core still lists them in
 * wp-sitemap.xml. That contradiction is visible to Google: the sitemap asks it
 * to index four /service-category/ URLs whose own markup says noindex, and
 * Search Console rejects an indexing request for them on exactly that basis.
 *
 * Nothing on the site links to those archives either, so they are dropped from
 * the sitemap rather than un-noindexed. If they are ever wanted as real landing
 * pages they need unique copy and internal links first, at which point this
 * filter and the connector's rule both need revisiting.
 */
add_filter('wp_sitemaps_taxonomies', function ($taxonomies) {
    unset($taxonomies['service_category']);
    return $taxonomies;
});

/*
 * The same decision, stated to the connector. Connector 1.17.1 stops blanket
 * noindexing custom taxonomies, so without this the archives would quietly
 * become indexable again on the next connector update while still being absent
 * from the sitemap. Opting in keeps both halves deliberate and matching. Harmless
 * on 1.16.x, which has no such filter. Remove this and the sitemap filter above
 * together if these ever become real landing pages.
 */
add_filter('voa_thin_archives', function ($thin) {
    if (!isset($thin['taxonomies']) || !is_array($thin['taxonomies'])) {
        $thin['taxonomies'] = array();
    }
    $thin['taxonomies'][] = 'service_category';
    return $thin;
});

/* ─── Sitemap status ────────────────────────────────────────────
 * WordPress matches the wp-sitemap rewrite rules and renders the XML, but the
 * main query finds no posts so handle_404() has already stamped the response
 * 404 by the time the sitemap renders. The body is correct and the status is
 * wrong, which is the worst combination: Google refuses a sitemap that 404s,
 * so the whole site would stay unsubmitted.
 *
 * Runs before the sitemap renderer (priority 0 on template_redirect) and both
 * clears the 404 flag and re-stamps the status, since headers are still
 * buffered at this point.
 */
add_action('template_redirect', function () {
    if (get_query_var('sitemap') || get_query_var('sitemap-stylesheet')) {
        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
    }
}, 0);

/* ─── Legacy URL redirects ──────────────────────────────────────
 * The static demo linked to /services/ style paths; anything that used to sit
 * under /service/ (WordPress's default CPT slug) is folded into /services/.
 */
add_action('template_redirect', function () {
    if (!is_404()) return;
    $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    if ($path === '') return;

    $to = '';
    if ($path === 'service' || strpos($path, 'service/') === 0) {
        $to = '/' . preg_replace('#^service(/|$)#', 'services/', $path);
    } elseif (strpos($path, 'index.html') !== false) {
        $to = '/';
    }
    if ($to) {
        wp_safe_redirect(home_url($to), 301);
        exit;
    }
});
