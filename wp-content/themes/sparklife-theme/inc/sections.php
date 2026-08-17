<?php
/**
 * CC Fields section types for the Spark Life design system.
 *
 * CC Fields ships generic plumbing (editor UI, storage, renderer, seeder) plus a
 * default set of section types for another site. Registering ours through the
 * `ccf_registered_sections` filter *replaces* that default set, so the page
 * editor only offers sections this theme can actually render.
 *
 * The matching markup lives in this theme at cc-fields/sections/{type}.php —
 * the CC Fields renderer prefers a theme template over its own bundled one.
 *
 * Field vocabulary handled by the plugin: text, textarea, wysiwyg, image, url,
 * select, number, toggle, color, repeater (+ sub_fields).
 */
if (!defined('ABSPATH')) exit;

/** Shared background-variant control → .section--paper / --white / --ink / --blue. */
function sl_bg_field($default = 'white') {
    return array(
        'name' => 'bg', 'label' => 'Background', 'type' => 'select', 'default' => $default,
        'options' => array(
            'white' => 'White',
            'paper' => 'Paper (off-white)',
            'ink'   => 'Ink (dark)',
            'blue'  => 'Electric blue',
        ),
    );
}

function sl_registered_sections() {
    return array(

        /* ─── Home hero ────────────────────────────────────────────── */
        'hero' => array(
            'label' => 'Home Hero',
            'description' => 'Big skewed headline with a highlighted word, lead, CTAs, trust stats and the “fast quote” card.',
            'fields' => array(
                array('name' => 'eyebrow', 'label' => 'Eyebrow (with pulse dot)', 'type' => 'text', 'default' => ''),
                array('name' => 'title', 'label' => 'Title', 'type' => 'textarea', 'default' => ''),
                array('name' => 'title_highlight', 'label' => 'Highlighted word (blue, underlined)', 'type' => 'text', 'default' => ''),
                array('name' => 'lead', 'label' => 'Lead paragraph', 'type' => 'textarea', 'default' => ''),
                array('name' => 'primary_btn_text', 'label' => 'Primary button text', 'type' => 'text', 'default' => ''),
                array('name' => 'primary_btn_url', 'label' => 'Primary button URL', 'type' => 'text', 'default' => ''),
                array('name' => 'secondary_btn_text', 'label' => 'Secondary button text', 'type' => 'text', 'default' => ''),
                array('name' => 'secondary_btn_url', 'label' => 'Secondary button URL (blank = call us)', 'type' => 'text', 'default' => ''),
                array('name' => 'trust', 'label' => 'Trust items', 'type' => 'repeater', 'sub_fields' => array(
                    array('name' => 'value', 'label' => 'Value (or leave blank for stars)', 'type' => 'text'),
                    array('name' => 'label', 'label' => 'Label', 'type' => 'text'),
                    array('name' => 'stars', 'label' => 'Show 5 stars instead of a value', 'type' => 'toggle', 'default' => '0'),
                )),
                array('name' => 'card_tag', 'label' => 'Card tag', 'type' => 'text', 'default' => '⚡ Fast quote'),
                array('name' => 'card_title', 'label' => 'Card title', 'type' => 'text', 'default' => ''),
                array('name' => 'card_text', 'label' => 'Card text', 'type' => 'textarea', 'default' => ''),
                array('name' => 'card_btn_text', 'label' => 'Card button text', 'type' => 'text', 'default' => 'Book a visit'),
                array('name' => 'card_btn_url', 'label' => 'Card button URL', 'type' => 'text', 'default' => '#quote'),
                array('name' => 'card_foot', 'label' => 'Card footer line', 'type' => 'textarea', 'default' => ''),
            ),
        ),

        /* ─── Inner page hero ──────────────────────────────────────── */
        'pagehero' => array(
            'label' => 'Page Hero',
            'description' => 'Inner-page header: eyebrow, skewed title, lead and optional CTAs.',
            'fields' => array(
                array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                array('name' => 'title', 'label' => 'Title', 'type' => 'textarea', 'default' => ''),
                array('name' => 'title_highlight', 'label' => 'Highlighted word (blue)', 'type' => 'text', 'default' => ''),
                array('name' => 'lead', 'label' => 'Lead paragraph', 'type' => 'textarea', 'default' => ''),
                array('name' => 'bg_image', 'label' => 'Background image', 'type' => 'image', 'default' => ''),
                array('name' => 'bg_image_url', 'label' => 'Background image URL (fallback)', 'type' => 'url', 'default' => ''),
                array('name' => 'show_actions', 'label' => 'Show CTA buttons', 'type' => 'toggle', 'default' => '1'),
                array('name' => 'primary_btn_text', 'label' => 'Primary button text', 'type' => 'text', 'default' => 'Get a quote'),
                array('name' => 'primary_btn_url', 'label' => 'Primary button URL', 'type' => 'text', 'default' => '/contact/'),
                array('name' => 'secondary_btn_text', 'label' => 'Secondary button text', 'type' => 'text', 'default' => ''),
                array('name' => 'secondary_btn_url', 'label' => 'Secondary button URL (blank = call us)', 'type' => 'text', 'default' => ''),
                array('name' => 'show_breadcrumb', 'label' => 'Show breadcrumb', 'type' => 'toggle', 'default' => '1'),
            ),
        ),

        /* ─── Marquee ──────────────────────────────────────────────── */
        'marquee' => array(
            'label' => 'Marquee',
            'description' => 'Blue scrolling band of service names. Leave the items empty to use the live Services list.',
            'fields' => array(
                array('name' => 'items', 'label' => 'Items', 'type' => 'repeater', 'sub_fields' => array(
                    array('name' => 'text', 'label' => 'Text', 'type' => 'text'),
                )),
            ),
        ),

        /* ─── Services bento (home) ────────────────────────────────── */
        'services_bento' => array(
            'label' => 'Services Bento',
            'description' => 'The bento grid of service cards. Pulls live Services (CPT) — the “Feature on home page” and “Accent card” flags on each service control the large dark and blue tiles.',
            'fields' => array(
                sl_bg_field('white'),
                array('name' => 'kicker', 'label' => 'Kicker', 'type' => 'text', 'default' => 'What we do'),
                array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                array('name' => 'heading_highlight', 'label' => 'Highlighted part of heading', 'type' => 'text', 'default' => ''),
                array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                array('name' => 'limit', 'label' => 'Max services shown (0 = all)', 'type' => 'number', 'default' => '7'),
                array('name' => 'category', 'label' => 'Limit to service category (slug, optional)', 'type' => 'text', 'default' => ''),
                array('name' => 'note', 'label' => 'Footnote', 'type' => 'textarea', 'default' => ''),
                array('name' => 'note_link_text', 'label' => 'Footnote link text', 'type' => 'text', 'default' => 'See all services'),
                array('name' => 'note_link_url', 'label' => 'Footnote link URL', 'type' => 'text', 'default' => '/services/'),
            ),
        ),

        /* ─── Services list (services landing page) ────────────────── */
        'services_list' => array(
            'label' => 'Services List',
            'description' => 'Full list of live Services as linked cards with icon, summary and price-from.',
            'fields' => array(
                sl_bg_field('white'),
                array('name' => 'kicker', 'label' => 'Kicker', 'type' => 'text', 'default' => ''),
                array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                array('name' => 'heading_highlight', 'label' => 'Highlighted part of heading', 'type' => 'text', 'default' => ''),
                array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                array('name' => 'category', 'label' => 'Limit to service category (slug, optional)', 'type' => 'text', 'default' => ''),
                array('name' => 'limit', 'label' => 'Max services shown (0 = all)', 'type' => 'number', 'default' => '0'),
                array('name' => 'exclude_current', 'label' => 'Hide the service being viewed', 'type' => 'toggle', 'default' => '1'),
            ),
        ),

        /* ─── Why us + stats ──────────────────────────────────────── */
        'why_stats' => array(
            'label' => 'Why Us + Stats',
            'description' => 'Dark two-column block: ticked reasons on the left, animated stat tiles on the right.',
            'fields' => array(
                sl_bg_field('ink'),
                array('name' => 'kicker', 'label' => 'Kicker', 'type' => 'text', 'default' => ''),
                array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                array('name' => 'items', 'label' => 'Reasons', 'type' => 'repeater', 'sub_fields' => array(
                    array('name' => 'title', 'label' => 'Title (bold lead-in)', 'type' => 'text'),
                    array('name' => 'desc', 'label' => 'Description', 'type' => 'textarea'),
                )),
                array('name' => 'stats', 'label' => 'Stats', 'type' => 'repeater', 'sub_fields' => array(
                    array('name' => 'number', 'label' => 'Number', 'type' => 'text'),
                    array('name' => 'suffix', 'label' => 'Suffix (+, %, min…)', 'type' => 'text'),
                    array('name' => 'decimals', 'label' => 'Decimal places', 'type' => 'number', 'default' => '0'),
                    array('name' => 'label', 'label' => 'Label', 'type' => 'text'),
                )),
            ),
        ),

        /* ─── Process steps ───────────────────────────────────────── */
        'process_steps' => array(
            'label' => 'Process Steps',
            'description' => 'Numbered “how it works” cards (01 / 02 / 03).',
            'fields' => array(
                sl_bg_field('paper'),
                array('name' => 'kicker', 'label' => 'Kicker', 'type' => 'text', 'default' => 'How it works'),
                array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                array('name' => 'heading_highlight', 'label' => 'Highlighted part of heading', 'type' => 'text', 'default' => ''),
                array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                array('name' => 'steps', 'label' => 'Steps', 'type' => 'repeater', 'sub_fields' => array(
                    array('name' => 'title', 'label' => 'Title', 'type' => 'text'),
                    array('name' => 'desc', 'label' => 'Description', 'type' => 'textarea'),
                )),
            ),
        ),

        /* ─── About / badge ───────────────────────────────────────── */
        'about_badge' => array(
            'label' => 'About + Badge',
            'description' => 'The rotating dashed badge with the logo (or a photo) beside a copy block.',
            'fields' => array(
                sl_bg_field('white'),
                array('name' => 'kicker', 'label' => 'Kicker', 'type' => 'text', 'default' => 'Meet the team'),
                array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                array('name' => 'heading_highlight', 'label' => 'Highlighted part of heading', 'type' => 'text', 'default' => ''),
                array('name' => 'body', 'label' => 'Body', 'type' => 'wysiwyg', 'default' => ''),
                array('name' => 'image', 'label' => 'Image (blank = logo badge)', 'type' => 'image', 'default' => ''),
                array('name' => 'image_url', 'label' => 'Image URL (fallback)', 'type' => 'url', 'default' => ''),
                array('name' => 'sticker', 'label' => 'Sticker text', 'type' => 'text', 'default' => 'Est. Frankston'),
                array('name' => 'image_right', 'label' => 'Image on the right', 'type' => 'toggle', 'default' => '0'),
                array('name' => 'button_text', 'label' => 'Button text', 'type' => 'text', 'default' => ''),
                array('name' => 'button_url', 'label' => 'Button URL', 'type' => 'text', 'default' => ''),
            ),
        ),

        /* ─── Service row (alternating image / prose) ──────────────── */
        'service_row' => array(
            'label' => 'Service Row',
            'description' => 'Image beside a heading and prose — alternate the image side down a service page.',
            'fields' => array(
                sl_bg_field('white'),
                array('name' => 'kicker', 'label' => 'Kicker', 'type' => 'text', 'default' => ''),
                array('name' => 'number', 'label' => 'Number (e.g. 01)', 'type' => 'text', 'default' => ''),
                array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                array('name' => 'heading_highlight', 'label' => 'Highlighted part of heading', 'type' => 'text', 'default' => ''),
                array('name' => 'body', 'label' => 'Body', 'type' => 'wysiwyg', 'default' => ''),
                array('name' => 'image', 'label' => 'Image', 'type' => 'image', 'default' => ''),
                array('name' => 'image_url', 'label' => 'Image URL (fallback)', 'type' => 'url', 'default' => ''),
                array('name' => 'image_right', 'label' => 'Image on the right', 'type' => 'toggle', 'default' => '0'),
                array('name' => 'button_text', 'label' => 'Button text', 'type' => 'text', 'default' => ''),
                array('name' => 'button_url', 'label' => 'Button URL', 'type' => 'text', 'default' => ''),
            ),
        ),

        /* ─── Tick list ───────────────────────────────────────────── */
        'tick_list' => array(
            'label' => 'Tick List',
            'description' => 'Two-column checklist of what a job includes.',
            'fields' => array(
                sl_bg_field('paper'),
                array('name' => 'kicker', 'label' => 'Kicker', 'type' => 'text', 'default' => ''),
                array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                array('name' => 'heading_highlight', 'label' => 'Highlighted part of heading', 'type' => 'text', 'default' => ''),
                array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                array('name' => 'items', 'label' => 'Items', 'type' => 'repeater', 'sub_fields' => array(
                    array('name' => 'text', 'label' => 'Text', 'type' => 'text'),
                )),
            ),
        ),

        /* ─── Reviews ─────────────────────────────────────────────── */
        'reviews' => array(
            'label' => 'Reviews',
            'description' => 'Grid of review cards — tick “Highlight” on one to render it dark.',
            'fields' => array(
                sl_bg_field('paper'),
                array('name' => 'kicker', 'label' => 'Kicker', 'type' => 'text', 'default' => ''),
                array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                array('name' => 'heading_highlight', 'label' => 'Highlighted part of heading', 'type' => 'text', 'default' => ''),
                array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                array('name' => 'reviews', 'label' => 'Reviews', 'type' => 'repeater', 'sub_fields' => array(
                    array('name' => 'text', 'label' => 'Quote', 'type' => 'textarea'),
                    array('name' => 'name', 'label' => 'Name', 'type' => 'text'),
                    array('name' => 'subtitle', 'label' => 'Suburb / job', 'type' => 'text'),
                    array('name' => 'highlight', 'label' => 'Highlight (dark card)', 'type' => 'toggle', 'default' => '0'),
                )),
            ),
        ),

        /* ─── Service areas ───────────────────────────────────────── */
        'service_areas' => array(
            'label' => 'Service Areas',
            'description' => 'Blue section listing the suburbs covered as pills.',
            'fields' => array(
                array('name' => 'kicker', 'label' => 'Kicker', 'type' => 'text', 'default' => 'Where we work'),
                array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                array('name' => 'suburbs', 'label' => 'Suburbs (one per line)', 'type' => 'textarea', 'default' => ''),
            ),
        ),

        /* ─── Quote / contact form ────────────────────────────────── */
        'quote_form' => array(
            'label' => 'Quote Form',
            'description' => 'Contact details beside the enquiry form. The service dropdown is built from live Services.',
            'fields' => array(
                sl_bg_field('paper'),
                array('name' => 'anchor', 'label' => 'Anchor id', 'type' => 'text', 'default' => 'quote'),
                array('name' => 'kicker', 'label' => 'Kicker', 'type' => 'text', 'default' => 'Free quote'),
                array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                array('name' => 'heading_highlight', 'label' => 'Highlighted part of heading', 'type' => 'text', 'default' => ''),
                array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                array('name' => 'show_details', 'label' => 'Show phone / email rows', 'type' => 'toggle', 'default' => '1'),
                array('name' => 'form_button_text', 'label' => 'Form button text', 'type' => 'text', 'default' => 'Send my request ⚡'),
                array('name' => 'form_note', 'label' => 'Note under the button', 'type' => 'text', 'default' => 'No spam, ever. We only use your details to quote your job.'),
                array('name' => 'preselect_service', 'label' => 'Pre-select the current service', 'type' => 'toggle', 'default' => '1'),
            ),
        ),

        /* ─── FAQ ─────────────────────────────────────────────────── */
        'faq' => array(
            'label' => 'FAQ',
            'description' => 'Accordion of questions and answers (also emits FAQ schema).',
            'fields' => array(
                sl_bg_field('white'),
                array('name' => 'kicker', 'label' => 'Kicker', 'type' => 'text', 'default' => 'Good questions'),
                array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                array('name' => 'heading_highlight', 'label' => 'Highlighted part of heading', 'type' => 'text', 'default' => ''),
                array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                array('name' => 'faqs', 'label' => 'FAQs', 'type' => 'repeater', 'sub_fields' => array(
                    array('name' => 'question', 'label' => 'Question', 'type' => 'text'),
                    array('name' => 'answer', 'label' => 'Answer', 'type' => 'wysiwyg'),
                )),
            ),
        ),

        /* ─── Gallery ─────────────────────────────────────────────── */
        'gallery_grid' => array(
            'label' => 'Gallery Grid',
            'description' => 'Grid of job photos with optional tall / wide tiles.',
            'fields' => array(
                sl_bg_field('white'),
                array('name' => 'kicker', 'label' => 'Kicker', 'type' => 'text', 'default' => ''),
                array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                array('name' => 'heading_highlight', 'label' => 'Highlighted part of heading', 'type' => 'text', 'default' => ''),
                array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                array('name' => 'images', 'label' => 'Images', 'type' => 'repeater', 'sub_fields' => array(
                    array('name' => 'image', 'label' => 'Image', 'type' => 'image'),
                    array('name' => 'image_url', 'label' => 'Image URL (fallback)', 'type' => 'url'),
                    array('name' => 'alt', 'label' => 'Caption / alt text', 'type' => 'text'),
                    array('name' => 'size', 'label' => 'Size', 'type' => 'select', 'default' => 'normal',
                          'options' => array('normal' => 'Normal', 'tall' => 'Tall', 'wide' => 'Wide')),
                )),
            ),
        ),

        /* ─── Text block ──────────────────────────────────────────── */
        'text_block' => array(
            'label' => 'Text Block',
            'description' => 'Rich-text prose with an optional heading. Used for Privacy, Terms and long-form copy.',
            'fields' => array(
                sl_bg_field('white'),
                array('name' => 'kicker', 'label' => 'Kicker', 'type' => 'text', 'default' => ''),
                array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                array('name' => 'heading_highlight', 'label' => 'Highlighted part of heading', 'type' => 'text', 'default' => ''),
                array('name' => 'content', 'label' => 'Content', 'type' => 'wysiwyg', 'default' => ''),
                array('name' => 'max_width', 'label' => 'Max width', 'type' => 'select', 'default' => 'normal',
                      'options' => array('narrow' => 'Narrow', 'normal' => 'Normal', 'wide' => 'Wide')),
            ),
        ),

        /* ─── Map ─────────────────────────────────────────────────── */
        'embed_map' => array(
            'label' => 'Map Embed',
            'description' => 'Embedded Google map of the service area.',
            'fields' => array(
                sl_bg_field('paper'),
                array('name' => 'kicker', 'label' => 'Kicker', 'type' => 'text', 'default' => ''),
                array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                array('name' => 'embed_url', 'label' => 'Google Maps embed URL', 'type' => 'url', 'default' => ''),
            ),
        ),

        /* ─── Closing CTA band ────────────────────────────────────── */
        'cta_band' => array(
            'label' => 'CTA Band',
            'description' => 'Dark closing call-to-action with the blue glow.',
            'fields' => array(
                array('name' => 'title', 'label' => 'Title', 'type' => 'textarea', 'default' => ''),
                array('name' => 'lead', 'label' => 'Lead', 'type' => 'textarea', 'default' => ''),
                array('name' => 'primary_btn_text', 'label' => 'Primary button text', 'type' => 'text', 'default' => 'Get your free quote'),
                array('name' => 'primary_btn_url', 'label' => 'Primary button URL', 'type' => 'text', 'default' => '/contact/'),
                array('name' => 'secondary_btn_text', 'label' => 'Secondary button text', 'type' => 'text', 'default' => ''),
                array('name' => 'secondary_btn_url', 'label' => 'Secondary button URL (blank = call us)', 'type' => 'text', 'default' => ''),
            ),
        ),

        /* ─── Spacer ──────────────────────────────────────────────── */
        'spacer' => array(
            'label' => 'Spacer',
            'description' => 'Vertical spacing between sections.',
            'fields' => array(
                sl_bg_field('white'),
                array('name' => 'height', 'label' => 'Height (px)', 'type' => 'number', 'default' => '60'),
            ),
        ),
    );
}

/** Replace CC Fields' bundled section types with the Spark Life set. */
add_filter('ccf_registered_sections', function ($sections) {
    return sl_registered_sections();
}, 20);
