<?php
/**
 * CC Fields Core - Admin meta box and section management
 */
if (!defined('ABSPATH')) exit;

class Ccf_Fields_Core {

    public function init() {
        add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
        add_action('save_post', array($this, 'save_sections'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_ccf_get_section_fields', array($this, 'ajax_get_section_fields'));
        add_action('wp_ajax_ccf_upload_image', array($this, 'ajax_upload_image'));
    }

    public function register_meta_boxes() {
        $post_types = apply_filters('ccf_post_types', array('page', 'post'));
        foreach ($post_types as $pt) {
            add_meta_box(
                'ccf_sections',
                'Page Sections',
                array($this, 'render_meta_box'),
                $pt,
                'normal',
                'high'
            );
        }
    }

    public function enqueue_admin_assets($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) return;

        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script(
            'ccf-admin',
            CCF_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable', 'wp-color-picker'),
            CCF_VERSION,
            true
        );
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style(
            'ccf-font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
            array(),
            '6.5.1'
        );
        wp_enqueue_style(
            'ccf-admin',
            CCF_URL . 'assets/css/admin.css',
            array(),
            CCF_VERSION
        );
        wp_localize_script('ccf-admin', 'ccfAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ccf_nonce'),
            'sections' => Ccf_Sections::get_registered_sections(),
        ));
    }

    public function render_meta_box($post) {
        wp_nonce_field('ccf_save_sections', 'ccf_sections_nonce');
        $saved_sections = get_post_meta($post->ID, '_ccf_sections', true);
        if (!is_array($saved_sections)) $saved_sections = array();
        $registered = Ccf_Sections::get_registered_sections();
        ?>
        <div id="ccf-sections-wrapper">
            <div id="ccf-sections-list" class="ccf-sortable">
                <?php
                foreach ($saved_sections as $index => $section) {
                    if (isset($registered[$section['type']])) {
                        $this->render_section_panel($section['type'], $registered[$section['type']], $section['data'], $index);
                    }
                }
                ?>
            </div>

            <div id="ccf-add-section">
                <button type="button" class="button button-primary button-large" id="ccf-add-section-btn">
                    + Add Section
                </button>
                <div id="ccf-section-picker" style="display:none;">
                    <div class="ccf-section-picker-grid">
                        <?php foreach ($registered as $key => $sec): ?>
                        <div class="ccf-section-option" data-section="<?php echo esc_attr($key); ?>">
                            <span class="ccf-section-icon"><?php echo $sec['icon']; ?></span>
                            <span class="ccf-section-label"><?php echo esc_html($sec['label']); ?></span>
                            <span class="ccf-section-desc"><?php echo esc_html($sec['description']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_section_panel($type, $config, $data, $index) {
        $collapsed = isset($data['_collapsed']) ? $data['_collapsed'] : false;
        ?>
        <div class="ccf-section-panel <?php echo $collapsed ? 'collapsed' : ''; ?>" data-type="<?php echo esc_attr($type); ?>" data-index="<?php echo $index; ?>">
            <div class="ccf-section-header">
                <span class="ccf-drag-handle">&#9776;</span>
                <span class="ccf-section-icon-sm"><?php echo $config['icon']; ?></span>
                <span class="ccf-section-title"><?php echo esc_html($config['label']); ?></span>
                <span class="ccf-section-preview-text"><?php echo esc_html($this->get_preview_text($config, $data)); ?></span>
                <div class="ccf-section-actions">
                    <button type="button" class="ccf-btn-toggle" title="Toggle">&#9660;</button>
                    <button type="button" class="ccf-btn-duplicate" title="Duplicate">&#10697;</button>
                    <button type="button" class="ccf-btn-delete" title="Delete">&times;</button>
                </div>
            </div>
            <div class="ccf-section-body">
                <input type="hidden" name="ccf_sections[<?php echo $index; ?>][type]" value="<?php echo esc_attr($type); ?>">
                <?php $this->render_fields($config['fields'], $data, "ccf_sections[{$index}][data]"); ?>
            </div>
        </div>
        <?php
    }

    private function get_preview_text($config, $data) {
        // Show a short preview from first text field
        foreach ($config['fields'] as $field) {
            if (in_array($field['type'], array('text', 'textarea')) && !empty($data[$field['name']])) {
                return wp_trim_words($data[$field['name']], 8, '...');
            }
        }
        return '';
    }

    public function render_fields($fields, $data, $name_prefix) {
        foreach ($fields as $field) {
            $value = isset($data[$field['name']]) ? $data[$field['name']] : (isset($field['default']) ? $field['default'] : '');
            $field_name = $name_prefix . '[' . $field['name'] . ']';
            $field_id = sanitize_title($name_prefix . '_' . $field['name']);

            echo '<div class="ccf-field ccf-field-' . esc_attr($field['type']) . '">';
            if (!empty($field['label'])) {
                echo '<label for="' . esc_attr($field_id) . '">' . esc_html($field['label']) . '</label>';
            }
            if (!empty($field['description'])) {
                echo '<p class="ccf-field-desc">' . esc_html($field['description']) . '</p>';
            }

            switch ($field['type']) {
                case 'text':
                    echo '<input type="text" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" class="widefat">';
                    break;

                case 'textarea':
                    echo '<textarea id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" rows="4" class="widefat">' . esc_textarea($value) . '</textarea>';
                    break;

                case 'wysiwyg':
                    $editor_id = 'ccf_editor_' . $field_id;
                    wp_editor($value, $editor_id, array(
                        'textarea_name' => $field_name,
                        'textarea_rows' => 6,
                        'media_buttons' => true,
                        'teeny' => false,
                    ));
                    break;

                case 'image':
                    $img_url = $value ? wp_get_attachment_image_url($value, 'medium') : '';
                    echo '<div class="ccf-image-field">';
                    echo '<input type="hidden" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" class="ccf-image-id">';
                    echo '<div class="ccf-image-preview">' . ($img_url ? '<img src="' . esc_url($img_url) . '">' : '') . '</div>';
                    echo '<button type="button" class="button mgf-upload-image">Choose Image</button>';
                    echo '<button type="button" class="button mgf-remove-image" ' . (!$value ? 'style="display:none"' : '') . '>Remove</button>';
                    echo '</div>';
                    break;

                case 'select':
                    echo '<select id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" class="widefat">';
                    foreach ($field['options'] as $opt_val => $opt_label) {
                        echo '<option value="' . esc_attr($opt_val) . '" ' . selected($value, $opt_val, false) . '>' . esc_html($opt_label) . '</option>';
                    }
                    echo '</select>';
                    break;

                case 'color':
                    echo '<input type="text" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" class="ccf-color-picker">';
                    break;

                case 'number':
                    echo '<input type="number" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" class="small-text">';
                    break;

                case 'toggle':
                    echo '<label class="ccf-toggle">';
                    echo '<input type="checkbox" name="' . esc_attr($field_name) . '" value="1" ' . checked($value, '1', false) . '>';
                    echo '<span class="ccf-toggle-slider"></span>';
                    echo '</label>';
                    break;

                case 'url':
                    echo '<input type="url" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_url($value) . '" class="widefat">';
                    break;

                case 'repeater':
                    $this->render_repeater($field, $value, $field_name);
                    break;

                case 'icon_select':
                    $this->render_icon_select($field, $value, $field_name, $field_id);
                    break;
            }

            echo '</div>';
        }
    }

    private function render_repeater($field, $values, $name_prefix) {
        if (!is_array($values)) $values = array();
        echo '<div class="ccf-repeater" data-fields=\'' . esc_attr(json_encode($field['sub_fields'])) . '\'>';
        echo '<div class="ccf-repeater-items ccf-sortable">';

        foreach ($values as $i => $row) {
            echo '<div class="ccf-repeater-item" data-index="' . $i . '">';
            echo '<div class="ccf-repeater-item-header"><span class="ccf-drag-handle">&#9776;</span><span class="ccf-repeater-item-title">Item ' . ($i + 1) . '</span><button type="button" class="ccf-btn-delete-item">&times;</button></div>';
            echo '<div class="ccf-repeater-item-body">';
            $this->render_fields($field['sub_fields'], $row, $name_prefix . '[' . $i . ']');
            echo '</div></div>';
        }

        echo '</div>';
        echo '<button type="button" class="button mgf-add-repeater-item">+ Add Item</button>';
        echo '</div>';
    }

    private function render_icon_select($field, $value, $name, $id) {
        $icons = isset($field['icons']) ? $field['icons'] : array();
        echo '<div class="ccf-icon-select">';
        echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" class="ccf-icon-value">';
        echo '<div class="ccf-icon-grid">';
        foreach ($icons as $icon_key => $icon_svg) {
            $selected = ($value === $icon_key) ? 'selected' : '';
            echo '<div class="ccf-icon-option ' . $selected . '" data-icon="' . esc_attr($icon_key) . '">' . $icon_svg . '</div>';
        }
        echo '</div></div>';
    }

    public function save_sections($post_id, $post) {
        if (!isset($_POST['ccf_sections_nonce'])) return;
        if (!wp_verify_nonce($_POST['ccf_sections_nonce'], 'ccf_save_sections')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $sections = array();
        if (isset($_POST['ccf_sections']) && is_array($_POST['ccf_sections'])) {
            foreach ($_POST['ccf_sections'] as $section) {
                if (isset($section['type'])) {
                    $sections[] = array(
                        'type' => sanitize_text_field($section['type']),
                        'data' => isset($section['data']) ? $this->sanitize_section_data($section['data']) : array(),
                    );
                }
            }
        }

        update_post_meta($post_id, '_ccf_sections', $sections);
    }

    private function sanitize_section_data($data) {
        if (!is_array($data)) return array();
        $clean = array();
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $clean[sanitize_key($key)] = $this->sanitize_section_data($val);
            } else {
                // Allow HTML in content fields
                $clean[sanitize_key($key)] = wp_kses_post($val);
            }
        }
        return $clean;
    }

    public function ajax_get_section_fields() {
        check_ajax_referer('ccf_nonce', 'nonce');
        $type = sanitize_text_field($_POST['section_type']);
        $index = intval($_POST['index']);
        $registered = Ccf_Sections::get_registered_sections();

        if (!isset($registered[$type])) {
            wp_send_json_error('Unknown section type');
        }

        ob_start();
        $this->render_section_panel($type, $registered[$type], array(), $index);
        $html = ob_get_clean();
        wp_send_json_success(array('html' => $html));
    }

    public function ajax_upload_image() {
        check_ajax_referer('ccf_nonce', 'nonce');
        wp_send_json_success();
    }
}
