<?php
/**
 * MG Global Variables - Site-wide variables accessible via shortcodes and template tags
 */
if (!defined('ABSPATH')) exit;

class Ccf_Global_Vars {

    public function init() {
        add_action('admin_menu', array($this, 'add_admin_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('ccf_var', array($this, 'shortcode_handler'));
        add_filter('the_content', array($this, 'replace_template_tags'));
    }

    public function add_admin_page() {
        add_menu_page(
            'CC Fields',
            'CC Fields',
            'manage_options',
            'cc-fields',
            array($this, 'render_admin_page'),
            'dashicons-layout',
            30
        );
        add_submenu_page(
            'cc-fields',
            'Global Variables',
            'Global Variables',
            'manage_options',
            'cc-fields-vars',
            array($this, 'render_vars_page')
        );
    }

    public function register_settings() {
        register_setting('ccf_global_vars_group', 'ccf_global_vars', array($this, 'sanitize_vars'));
        register_setting('ccf_global_vars_group', 'ccf_custom_vars', array($this, 'sanitize_custom_vars'));
    }

    public function render_admin_page() {
        ?>
        <div class="wrap ccf-admin-wrap">
            <h1>CC Fields</h1>
            <div class="ccf-admin-dashboard">
                <div class="ccf-admin-card">
                    <h2>Section Builder</h2>
                    <p>Add and manage page sections directly from the page editor. Edit any page or post to start adding sections.</p>
                    <a href="<?php echo admin_url('edit.php?post_type=page'); ?>" class="button button-primary">Edit Pages</a>
                </div>
                <div class="ccf-admin-card">
                    <h2>Global Variables</h2>
                    <p>Manage site-wide variables like phone numbers, addresses, and social links. Use them anywhere with shortcodes.</p>
                    <a href="<?php echo admin_url('admin.php?page=cc-fields-vars'); ?>" class="button button-primary">Manage Variables</a>
                </div>
                <div class="ccf-admin-card">
                    <h2>How to Use</h2>
                    <p>Use <code>[ccf_var key="company_phone"]</code> in any content area, or <code>{{company_phone}}</code> in section fields.</p>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_vars_page() {
        $vars = get_option('ccf_global_vars', array());
        $custom_vars = get_option('ccf_custom_vars', array());
        ?>
        <div class="wrap ccf-admin-wrap">
            <h1>Global Variables</h1>
            <p>These variables can be used anywhere on your site using shortcodes <code>[ccf_var key="variable_name"]</code> or template tags <code>{{variable_name}}</code></p>

            <form method="post" action="options.php">
                <?php settings_fields('ccf_global_vars_group'); ?>

                <h2>Company Information</h2>
                <table class="form-table">
                    <tr>
                        <th>Company Name</th>
                        <td><input type="text" name="ccf_global_vars[company_name]" value="<?php echo esc_attr($vars['company_name'] ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>Phone Number</th>
                        <td><input type="text" name="ccf_global_vars[company_phone]" value="<?php echo esc_attr($vars['company_phone'] ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>Email Address</th>
                        <td><input type="email" name="ccf_global_vars[company_email]" value="<?php echo esc_attr($vars['company_email'] ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>Street Address</th>
                        <td><input type="text" name="ccf_global_vars[company_address]" value="<?php echo esc_attr($vars['company_address'] ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>ABN</th>
                        <td><input type="text" name="ccf_global_vars[company_abn]" value="<?php echo esc_attr($vars['company_abn'] ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>Facebook URL</th>
                        <td><input type="url" name="ccf_global_vars[facebook_url]" value="<?php echo esc_url($vars['facebook_url'] ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>LinkedIn URL</th>
                        <td><input type="url" name="ccf_global_vars[linkedin_url]" value="<?php echo esc_url($vars['linkedin_url'] ?? ''); ?>" class="regular-text"></td>
                    </tr>
                </table>

                <h2>Custom Variables</h2>
                <p>Add your own variables that can be used site-wide.</p>
                <table class="form-table ccf-custom-vars-table" id="ccf-custom-vars">
                    <?php if (!empty($custom_vars)): foreach ($custom_vars as $i => $cv): ?>
                    <tr>
                        <td><input type="text" name="ccf_custom_vars[<?php echo $i; ?>][key]" value="<?php echo esc_attr($cv['key']); ?>" placeholder="Variable name" class="regular-text"></td>
                        <td><input type="text" name="ccf_custom_vars[<?php echo $i; ?>][value]" value="<?php echo esc_attr($cv['value']); ?>" placeholder="Value" class="regular-text"></td>
                        <td><button type="button" class="button ccf-remove-var">&times;</button></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </table>
                <button type="button" class="button" id="ccf-add-custom-var">+ Add Variable</button>

                <?php submit_button('Save Variables'); ?>
            </form>
        </div>
        <script>
        jQuery(function($) {
            var idx = <?php echo !empty($custom_vars) ? count($custom_vars) : 0; ?>;
            $('#ccf-add-custom-var').on('click', function() {
                var row = '<tr><td><input type="text" name="ccf_custom_vars[' + idx + '][key]" placeholder="Variable name" class="regular-text"></td><td><input type="text" name="ccf_custom_vars[' + idx + '][value]" placeholder="Value" class="regular-text"></td><td><button type="button" class="button ccf-remove-var">&times;</button></td></tr>';
                $('#ccf-custom-vars').append(row);
                idx++;
            });
            $(document).on('click', '.ccf-remove-var', function() {
                $(this).closest('tr').remove();
            });
        });
        </script>
        <?php
    }

    public function sanitize_vars($input) {
        $clean = array();
        foreach ($input as $key => $val) {
            $clean[sanitize_key($key)] = sanitize_text_field($val);
        }
        return $clean;
    }

    public function sanitize_custom_vars($input) {
        if (!is_array($input)) return array();
        $clean = array();
        foreach ($input as $cv) {
            if (!empty($cv['key'])) {
                $clean[] = array(
                    'key' => sanitize_key($cv['key']),
                    'value' => sanitize_text_field($cv['value']),
                );
            }
        }
        return $clean;
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array('key' => ''), $atts, 'ccf_var');
        return $this->get_var($atts['key']);
    }

    public function get_var($key) {
        $vars = get_option('ccf_global_vars', array());
        if (isset($vars[$key])) return esc_html($vars[$key]);

        $custom = get_option('ccf_custom_vars', array());
        foreach ($custom as $cv) {
            if ($cv['key'] === $key) return esc_html($cv['value']);
        }
        return '';
    }

    public function replace_template_tags($content) {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function($matches) {
            $val = $this->get_var($matches[1]);
            return $val ? $val : $matches[0];
        }, $content);
    }

    /**
     * Static helper to get a variable value
     */
    public static function get($key, $default = '') {
        $instance = new self();
        $val = $instance->get_var($key);
        return $val ? $val : $default;
    }
}
