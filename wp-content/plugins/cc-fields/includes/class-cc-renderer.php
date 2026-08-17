<?php
/**
 * MG Renderer - Renders sections on the frontend
 */
if (!defined('ABSPATH')) exit;

class Ccf_Renderer {

    public function init() {
        // Run at very high priority to ensure we override Divi and any other content filters
        add_filter('the_content', array($this, 'render_sections'), 999);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

        // Remove Divi builder content filter if it exists
        add_action('wp', array($this, 'maybe_disable_divi'));
    }

    /**
     * Disable Divi builder rendering for pages that have CC Fields sections
     */
    public function maybe_disable_divi() {
        if (!is_singular()) return;

        global $post;
        if (!$post) return;

        $sections = get_post_meta($post->ID, '_ccf_sections', true);
        if (!empty($sections) && is_array($sections)) {
            // Remove Divi's content filter
            remove_filter('the_content', 'et_builder_render_layout');
            // Remove Divi shortcode processing for this page
            remove_shortcode('et_pb_section');
            remove_shortcode('et_pb_row');
            remove_shortcode('et_pb_column');
            remove_shortcode('et_pb_text');
            remove_shortcode('et_pb_image');
            remove_shortcode('et_pb_button');
            remove_shortcode('et_pb_blurb');
            remove_shortcode('et_pb_cta');
            remove_shortcode('et_pb_divider');
            remove_shortcode('et_pb_signup');
            remove_shortcode('et_pb_contact_form');
            remove_shortcode('et_pb_contact_field');
        }
    }

    public function enqueue_frontend_assets() {
        // Front-end CSS and all interactive behaviour (reveal, count-up,
        // accordion, before/after slider, drawer) are owned by the theme's
        // assets/js/main.js, so CC Fields enqueues nothing on the front end.
    }

    public function render_sections($content) {
        if (!is_singular()) return $content;

        global $post;
        $sections = get_post_meta($post->ID, '_ccf_sections', true);
        if (empty($sections) || !is_array($sections)) return $content;

        $registered = Ccf_Sections::get_registered_sections();
        $output = '';

        foreach ($sections as $section) {
            $type = $section['type'];
            $data = isset($section['data']) ? $section['data'] : array();

            // Replace template tags in all string values
            $data = self::process_template_tags($data);

            // Check for theme template override first
            $template = locate_template('cc-fields/sections/' . $type . '.php');
            if (!$template) {
                $template = CCF_PATH . 'templates/sections/' . $type . '.php';
            }

            if (file_exists($template)) {
                ob_start();
                // Make data available to template
                $section_data = $data;
                $section_type = $type;
                $section_config = isset($registered[$type]) ? $registered[$type] : array();
                include $template;
                $output .= ob_get_clean();
            }
        }

        // If we have sections, replace the content with section output
        if ($output) {
            if (strpos($content, '[ccf_keep_content]') !== false) {
                return str_replace('[ccf_keep_content]', '', $content) . $output;
            }
            // Strip any remaining Divi shortcodes that weren't processed
            $output = preg_replace('/\[et_pb_[^\]]*\]/', '', $output);
            $output = preg_replace('/\[\/et_pb_[^\]]*\]/', '', $output);
            return $output;
        }

        // Even if no sections, strip Divi shortcodes from content
        $content = preg_replace('/\[et_pb_[^\]]*\]/', '', $content);
        $content = preg_replace('/\[\/et_pb_[^\]]*\]/', '', $content);

        return $content;
    }

    public static function process_template_tags($data) {
        if (!is_array($data)) {
            if (is_string($data)) {
                return preg_replace_callback('/\{\{(\w+)\}\}/', function($matches) {
                    return Ccf_Global_Vars::get($matches[1]);
                }, $data);
            }
            return $data;
        }
        foreach ($data as $key => $val) {
            $data[$key] = self::process_template_tags($val);
        }
        return $data;
    }

    /**
     * Helper: Get image URL from attachment ID
     */
    public static function get_image_url($id, $size = 'large') {
        if (!$id) return '';
        $url = wp_get_attachment_image_url($id, $size);
        return $url ? $url : '';
    }

    /**
     * Helper: Get background color class
     */
    public static function get_bg_class($bg) {
        $map = array(
            'white' => 'ccf-bg-white',
            'light' => 'ccf-bg-light',
            'dark' => 'ccf-bg-dark',
            'brand' => 'ccf-bg-brand',
            'gradient' => 'ccf-bg-gradient',
        );
        return isset($map[$bg]) ? $map[$bg] : 'ccf-bg-white';
    }

    /**
     * Helper: Check if section text should be light (on dark backgrounds)
     */
    public static function is_dark_bg($bg) {
        return in_array($bg, array('dark', 'brand', 'gradient'));
    }
}

/**
 * Global helper function to render sections for a post
 * Called by theme templates (page.php, single.php)
 */
function ccf_render_sections($post_id = 0) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $sections = get_post_meta($post_id, '_ccf_sections', true);

    if (!is_array($sections) || empty($sections)) {
        return;
    }

    $registered = Ccf_Sections::get_registered_sections();

    foreach ($sections as $section) {
        $type = isset($section['type']) ? $section['type'] : '';
        if (!$type) continue;

        // Support both nested ('data' key) and flat formats
        $data = isset($section['data']) ? $section['data'] : $section;
        $data = Ccf_Renderer::process_template_tags($data);

        $template = locate_template('cc-fields/sections/' . $type . '.php');
        if (!$template) {
            $template = CCF_PATH . 'templates/sections/' . $type . '.php';
        }

        if (file_exists($template)) {
            $section_data = $data;
            $section_type = $type;
            $section_config = isset($registered[$type]) ? $registered[$type] : array();
            include $template;
        }
    }
}

/**
 * Global helper function for getting a variable
 */
function ccf_get_var($key, $default = '') {
    return Ccf_Global_Vars::get($key, $default);
}
