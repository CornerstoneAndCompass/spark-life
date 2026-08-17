<?php
/**
 * CC Fields — registered section types.
 *
 * Each site that uses CC Fields ships its own section definitions. These are the
 * section types for the "Call The Plumber Guy" design system (lime / ink / paper,
 * Roboto). Field-type vocabulary (text, textarea, wysiwyg, image, url, select,
 * number, toggle, color, repeater + sub_fields, icon_select) is handled by the
 * generic core/editor/renderer — only the definitions below are site-specific.
 *
 * Templates live in templates/sections/{type}.php and receive $section_data.
 */
if (!defined('ABSPATH')) exit;

class Ccf_Sections {

    public function init() {
        // Sections are registered statically.
    }

    /** Shared background-variant control (maps to .section--paper / --white / --ink). */
    private static function bg_field($default = 'paper') {
        return array(
            'name' => 'bg', 'label' => 'Background', 'type' => 'select', 'default' => $default,
            'options' => array('paper' => 'Paper (cream)', 'white' => 'White', 'ink' => 'Ink (dark)'),
        );
    }

    public static function get_registered_sections() {
        $sections = array(

            // ─── Home Hero (image bg + lead form) ──────────────────────────
            'hero' => array(
                'label' => 'Home Hero',
                'description' => 'Full hero with background image, headline, CTAs, trust line and a lead-capture form.',
                'fields' => array(
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'title', 'label' => 'Title', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'title_highlight', 'label' => 'Highlighted word (lime)', 'type' => 'text', 'default' => ''),
                    array('name' => 'lead', 'label' => 'Lead paragraph', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'bg_image', 'label' => 'Background image', 'type' => 'image', 'default' => ''),
                    array('name' => 'bg_image_url', 'label' => 'Background image URL (fallback)', 'type' => 'url', 'default' => ''),
                    array('name' => 'primary_btn_text', 'label' => 'Primary button text', 'type' => 'text', 'default' => ''),
                    array('name' => 'primary_btn_url', 'label' => 'Primary button URL', 'type' => 'text', 'default' => ''),
                    array('name' => 'secondary_btn_text', 'label' => 'Secondary button text', 'type' => 'text', 'default' => ''),
                    array('name' => 'secondary_btn_url', 'label' => 'Secondary button URL', 'type' => 'text', 'default' => ''),
                    array('name' => 'trust_text', 'label' => 'Trust line (under buttons)', 'type' => 'text', 'default' => ''),
                    array('name' => 'show_form', 'label' => 'Show lead form', 'type' => 'toggle', 'default' => '1'),
                    array('name' => 'form_title', 'label' => 'Form title', 'type' => 'text', 'default' => 'Request a callback'),
                    array('name' => 'form_note', 'label' => 'Form note', 'type' => 'text', 'default' => ''),
                    array('name' => 'form_button_text', 'label' => 'Form button text', 'type' => 'text', 'default' => 'Get my callback'),
                ),
            ),

            // ─── Page Hero (dark header) ───────────────────────────────────
            'pagehero' => array(
                'label' => 'Page Hero',
                'description' => 'Dark page header with eyebrow, title, lead, breadcrumb and optional CTAs.',
                'fields' => array(
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'title', 'label' => 'Title', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'title_highlight', 'label' => 'Highlighted word (lime)', 'type' => 'text', 'default' => ''),
                    array('name' => 'lead', 'label' => 'Lead paragraph', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'bg_image', 'label' => 'Background image', 'type' => 'image', 'default' => ''),
                    array('name' => 'bg_image_url', 'label' => 'Background image URL (fallback)', 'type' => 'url', 'default' => ''),
                    array('name' => 'show_actions', 'label' => 'Show CTA buttons', 'type' => 'toggle', 'default' => '1'),
                    array('name' => 'primary_btn_text', 'label' => 'Primary button text', 'type' => 'text', 'default' => 'Call now'),
                    array('name' => 'primary_btn_url', 'label' => 'Primary button URL', 'type' => 'text', 'default' => ''),
                    array('name' => 'secondary_btn_text', 'label' => 'Secondary button text', 'type' => 'text', 'default' => ''),
                    array('name' => 'secondary_btn_url', 'label' => 'Secondary button URL', 'type' => 'text', 'default' => ''),
                ),
            ),

            // ─── Marquee (credentials band) ────────────────────────────────
            'marquee' => array(
                'label' => 'Marquee',
                'description' => 'Scrolling lime credentials band.',
                'fields' => array(
                    array('name' => 'items', 'label' => 'Items', 'type' => 'repeater', 'sub_fields' => array(
                        array('name' => 'text', 'label' => 'Text', 'type' => 'text'),
                    )),
                ),
            ),

            // ─── Intro + Stats ─────────────────────────────────────────────
            'intro_stats' => array(
                'label' => 'Intro + Stats',
                'description' => 'Two-column intro (heading + prose, optional image) above an animated stats row.',
                'fields' => array(
                    self::bg_field('paper'),
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'body', 'label' => 'Body', 'type' => 'wysiwyg', 'default' => ''),
                    array('name' => 'image', 'label' => 'Image', 'type' => 'image', 'default' => ''),
                    array('name' => 'image_url', 'label' => 'Image URL (fallback)', 'type' => 'url', 'default' => ''),
                    array('name' => 'button_text', 'label' => 'Button text', 'type' => 'text', 'default' => ''),
                    array('name' => 'button_url', 'label' => 'Button URL', 'type' => 'text', 'default' => ''),
                    array('name' => 'stats', 'label' => 'Stats', 'type' => 'repeater', 'sub_fields' => array(
                        array('name' => 'number', 'label' => 'Number', 'type' => 'text'),
                        array('name' => 'suffix', 'label' => 'Suffix (+, %, etc.)', 'type' => 'text'),
                        array('name' => 'label', 'label' => 'Label', 'type' => 'text'),
                        array('name' => 'plain', 'label' => 'Skip count-up animation', 'type' => 'toggle', 'default' => '0'),
                    )),
                ),
            ),

            // ─── Services Index (numbered rows) ────────────────────────────
            'services_index' => array(
                'label' => 'Services Index',
                'description' => 'Numbered, linked service rows in a 2-column grid.',
                'fields' => array(
                    self::bg_field('white'),
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'services', 'label' => 'Services', 'type' => 'repeater', 'sub_fields' => array(
                        array('name' => 'name', 'label' => 'Name', 'type' => 'text'),
                        array('name' => 'desc', 'label' => 'Description', 'type' => 'text'),
                        array('name' => 'url', 'label' => 'URL', 'type' => 'text'),
                    )),
                ),
            ),

            // ─── Why List (numbered reasons) ───────────────────────────────
            'why_list' => array(
                'label' => 'Why List',
                'description' => 'Sticky intro on the left, numbered reasons grid on the right.',
                'fields' => array(
                    self::bg_field('ink'),
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'items', 'label' => 'Reasons', 'type' => 'repeater', 'sub_fields' => array(
                        array('name' => 'title', 'label' => 'Title', 'type' => 'text'),
                        array('name' => 'desc', 'label' => 'Description', 'type' => 'textarea'),
                    )),
                ),
            ),

            // ─── Process Steps ─────────────────────────────────────────────
            'process_steps' => array(
                'label' => 'Process Steps',
                'description' => 'Numbered step cards (01 / 02 / 03).',
                'fields' => array(
                    self::bg_field('white'),
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'steps', 'label' => 'Steps', 'type' => 'repeater', 'sub_fields' => array(
                        array('name' => 'title', 'label' => 'Title', 'type' => 'text'),
                        array('name' => 'desc', 'label' => 'Description', 'type' => 'textarea'),
                    )),
                ),
            ),

            // ─── Before / After Slider ─────────────────────────────────────
            'before_after' => array(
                'label' => 'Before / After',
                'description' => 'Draggable before/after image comparison slider.',
                'fields' => array(
                    self::bg_field('paper'),
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'before_image', 'label' => 'Before image', 'type' => 'image', 'default' => ''),
                    array('name' => 'before_image_url', 'label' => 'Before image URL (fallback)', 'type' => 'url', 'default' => ''),
                    array('name' => 'after_image', 'label' => 'After image', 'type' => 'image', 'default' => ''),
                    array('name' => 'after_image_url', 'label' => 'After image URL (fallback)', 'type' => 'url', 'default' => ''),
                    array('name' => 'before_label', 'label' => 'Before label', 'type' => 'text', 'default' => 'Before'),
                    array('name' => 'after_label', 'label' => 'After label', 'type' => 'text', 'default' => 'After'),
                    array('name' => 'caption', 'label' => 'Caption / tag', 'type' => 'text', 'default' => ''),
                ),
            ),

            // ─── Service Areas ─────────────────────────────────────────────
            'service_areas' => array(
                'label' => 'Service Areas',
                'description' => 'Dark section listing service regions and their suburbs.',
                'fields' => array(
                    self::bg_field('ink'),
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'columns', 'label' => 'Regions', 'type' => 'repeater', 'sub_fields' => array(
                        array('name' => 'title', 'label' => 'Region title', 'type' => 'text'),
                        array('name' => 'suburbs', 'label' => 'Suburbs (one per line)', 'type' => 'textarea'),
                    )),
                ),
            ),

            // ─── Reviews ───────────────────────────────────────────────────
            'reviews' => array(
                'label' => 'Reviews',
                'description' => 'Google score plus a featured review and supporting review cards.',
                'fields' => array(
                    self::bg_field('paper'),
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'score', 'label' => 'Score', 'type' => 'text', 'default' => '5.0'),
                    array('name' => 'score_count', 'label' => 'Number of reviews', 'type' => 'text', 'default' => '36'),
                    array('name' => 'reviews', 'label' => 'Reviews', 'type' => 'repeater', 'sub_fields' => array(
                        array('name' => 'text', 'label' => 'Quote', 'type' => 'textarea'),
                        array('name' => 'name', 'label' => 'Name', 'type' => 'text'),
                        array('name' => 'subtitle', 'label' => 'Subtitle (suburb / job)', 'type' => 'text'),
                        array('name' => 'featured', 'label' => 'Featured (full-width, dark)', 'type' => 'toggle', 'default' => '0'),
                    )),
                ),
            ),

            // ─── FAQ ───────────────────────────────────────────────────────
            'faq' => array(
                'label' => 'FAQ',
                'description' => 'Accordion of questions and answers.',
                'fields' => array(
                    self::bg_field('white'),
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'faqs', 'label' => 'FAQs', 'type' => 'repeater', 'sub_fields' => array(
                        array('name' => 'question', 'label' => 'Question', 'type' => 'text'),
                        array('name' => 'answer', 'label' => 'Answer', 'type' => 'wysiwyg'),
                    )),
                ),
            ),

            // ─── Service Row (alt image / text) ────────────────────────────
            'service_row' => array(
                'label' => 'Service Row',
                'description' => 'Image beside a heading + prose block (alternating layout).',
                'fields' => array(
                    self::bg_field('paper'),
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'number', 'label' => 'Number (e.g. 01)', 'type' => 'text', 'default' => ''),
                    array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'body', 'label' => 'Body', 'type' => 'wysiwyg', 'default' => ''),
                    array('name' => 'image', 'label' => 'Image', 'type' => 'image', 'default' => ''),
                    array('name' => 'image_url', 'label' => 'Image URL (fallback)', 'type' => 'url', 'default' => ''),
                    array('name' => 'image_right', 'label' => 'Image on the right', 'type' => 'toggle', 'default' => '0'),
                    array('name' => 'button_text', 'label' => 'Button text', 'type' => 'text', 'default' => ''),
                    array('name' => 'button_url', 'label' => 'Button URL', 'type' => 'text', 'default' => ''),
                ),
            ),

            // ─── Tick List (what's included) ───────────────────────────────
            'tick_list' => array(
                'label' => 'Tick List',
                'description' => 'Two-column checklist of inclusions.',
                'fields' => array(
                    self::bg_field('white'),
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'items', 'label' => 'Items', 'type' => 'repeater', 'sub_fields' => array(
                        array('name' => 'text', 'label' => 'Text', 'type' => 'text'),
                    )),
                ),
            ),

            // ─── Emergency Band (lime CTA) ─────────────────────────────────
            'emergency_band' => array(
                'label' => 'Emergency Band',
                'description' => 'Lime call-out strip with a large phone CTA.',
                'fields' => array(
                    array('name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => ''),
                    array('name' => 'lead', 'label' => 'Lead', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'button_text', 'label' => 'Button text', 'type' => 'text', 'default' => ''),
                    array('name' => 'button_url', 'label' => 'Button URL', 'type' => 'text', 'default' => ''),
                ),
            ),

            // ─── CTA Band (ink) ────────────────────────────────────────────
            'cta_band' => array(
                'label' => 'CTA Band',
                'description' => 'Dark, centered closing call-to-action.',
                'fields' => array(
                    array('name' => 'title', 'label' => 'Title', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'lead', 'label' => 'Lead', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'primary_btn_text', 'label' => 'Primary button text', 'type' => 'text', 'default' => ''),
                    array('name' => 'primary_btn_url', 'label' => 'Primary button URL', 'type' => 'text', 'default' => ''),
                    array('name' => 'secondary_btn_text', 'label' => 'Secondary button text', 'type' => 'text', 'default' => ''),
                    array('name' => 'secondary_btn_url', 'label' => 'Secondary button URL', 'type' => 'text', 'default' => ''),
                ),
            ),

            // ─── Service Columns (3-up feature blocks) ─────────────────────
            'svc_columns' => array(
                'label' => 'Service Columns',
                'description' => 'Three feature blocks (heading + prose + list).',
                'fields' => array(
                    self::bg_field('paper'),
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'columns', 'label' => 'Columns', 'type' => 'repeater', 'sub_fields' => array(
                        array('name' => 'title', 'label' => 'Title', 'type' => 'text'),
                        array('name' => 'body', 'label' => 'Body', 'type' => 'textarea'),
                        array('name' => 'items', 'label' => 'List (one per line)', 'type' => 'textarea'),
                    )),
                ),
            ),

            // ─── Text Block (prose) ────────────────────────────────────────
            'text_block' => array(
                'label' => 'Text Block',
                'description' => 'Rich-text prose block with optional heading.',
                'fields' => array(
                    self::bg_field('paper'),
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'content', 'label' => 'Content', 'type' => 'wysiwyg', 'default' => ''),
                    array('name' => 'max_width', 'label' => 'Max width', 'type' => 'select', 'default' => 'normal', 'options' => array(
                        'narrow' => 'Narrow', 'normal' => 'Normal', 'wide' => 'Wide',
                    )),
                ),
            ),

            // ─── Gallery Grid ──────────────────────────────────────────────
            'gallery_grid' => array(
                'label' => 'Gallery Grid',
                'description' => 'Masonry-style gallery with tall/wide tiles.',
                'fields' => array(
                    self::bg_field('white'),
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'images', 'label' => 'Images', 'type' => 'repeater', 'sub_fields' => array(
                        array('name' => 'image', 'label' => 'Image', 'type' => 'image'),
                        array('name' => 'image_url', 'label' => 'Image URL (fallback)', 'type' => 'url'),
                        array('name' => 'alt', 'label' => 'Caption / alt', 'type' => 'text'),
                        array('name' => 'size', 'label' => 'Size', 'type' => 'select', 'options' => array(
                            'normal' => 'Normal', 'tall' => 'Tall', 'wide' => 'Wide',
                        )),
                    )),
                ),
            ),

            // ─── Embed Map ─────────────────────────────────────────────────
            'embed_map' => array(
                'label' => 'Map Embed',
                'description' => 'Embedded Google map.',
                'fields' => array(
                    self::bg_field('paper'),
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'embed_url', 'label' => 'Google Maps embed URL', 'type' => 'url', 'default' => ''),
                ),
            ),

            // ─── Contact (info + form) ─────────────────────────────────────
            'contact' => array(
                'label' => 'Contact',
                'description' => 'Contact details beside an enquiry form.',
                'fields' => array(
                    self::bg_field('white'),
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'form_title', 'label' => 'Form title', 'type' => 'text', 'default' => 'Send an enquiry'),
                    array('name' => 'form_note', 'label' => 'Form note', 'type' => 'text', 'default' => ''),
                    array('name' => 'form_button_text', 'label' => 'Form button text', 'type' => 'text', 'default' => 'Send enquiry'),
                ),
            ),

            // ─── Team Grid (preserves old-site team bios) ──────────────────
            'team_grid' => array(
                'label' => 'Team Grid',
                'description' => 'Grid of team members with photo, role and bio.',
                'fields' => array(
                    self::bg_field('paper'),
                    array('name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => ''),
                    array('name' => 'heading', 'label' => 'Heading', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'default' => ''),
                    array('name' => 'members', 'label' => 'Members', 'type' => 'repeater', 'sub_fields' => array(
                        array('name' => 'name', 'label' => 'Name', 'type' => 'text'),
                        array('name' => 'role', 'label' => 'Role', 'type' => 'text'),
                        array('name' => 'bio', 'label' => 'Bio', 'type' => 'textarea'),
                        array('name' => 'photo', 'label' => 'Photo', 'type' => 'image'),
                        array('name' => 'photo_url', 'label' => 'Photo URL (fallback)', 'type' => 'url'),
                    )),
                ),
            ),

            // ─── Spacer ────────────────────────────────────────────────────
            'spacer' => array(
                'label' => 'Spacer',
                'description' => 'Vertical spacing / divider.',
                'fields' => array(
                    self::bg_field('paper'),
                    array('name' => 'height', 'label' => 'Height (px)', 'type' => 'number', 'default' => '60'),
                ),
            ),

        );

        return apply_filters('ccf_registered_sections', $sections);
    }
}
