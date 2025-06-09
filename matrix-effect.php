<?php
/*
Plugin Name:Matrix Text Effect
Description: Replaces standard text with a matrix effect with a typewriter, disabling it from forms and by id.
Version:1.3.5
Author: Sergey_Vladimirovich
Author URI:https://anna-ivanovna.ru/matrix-effect//matrix-effect/
License:GPLv2 or later
Text Domain:matrix-effect
Domain Path: /languages
Donate link: https://anna-ivanovna.ru/matrix-effect//donate/
*/
class MatrixEffectPlugin {
public function __construct() {
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_filter('matrix_effect_should_disable', [$this, 'custom_disable_checks']);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_footer', array($this, 'add_html_structure'));
        add_action('wp_ajax_matrix_effect_update_settings', [$this, 'ajax_update_settings']);
        add_filter('pre_update_option_matrix_effect_options', [$this, 'sanitize_matrix_settings']);
    }
public function ajax_update_settings() {
    try {
        if (empty($_POST['nonce'] ?? '')) {
            throw new Exception(__('Nonce is required', 'matrix-effect'), 400);
        }
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']));
        if (!wp_verify_nonce($nonce, 'matrix_effect_nonce')) {
            throw new Exception(__('Invalid nonce', 'matrix-effect'), 403);
        }
        if (!current_user_can('manage_options')) {
            throw new Exception(__('Permission denied', 'matrix-effect'), 403);
        }
        $field = sanitize_text_field(wp_unslash($_POST['field'] ?? ''));
        $value = sanitize_text_field(wp_unslash($_POST['value'] ?? ''));
        if (empty($field) || empty($value)) {
            throw new Exception(__('Invalid data', 'matrix-effect'), 400);
        }
        $options = get_option('matrix_effect_options', []);
        $options[$field] = $value;
        if (!update_option('matrix_effect_options', $options)) {
            throw new Exception(__('Update failed', 'matrix-effect'), 500);
        }
        wp_send_json_success(__('Settings updated', 'matrix-effect'));

    } catch (Exception $e) {
        wp_send_json_error($e->getMessage(), $e->getCode());
    }
}
public function sanitize_matrix_settings($new_options) {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'matrixEffectSettings-options')) {
        return $new_options;
    }
    if (isset($_POST['matrix_effect_options']['text_bg_color_rgb'])) {
        $rgb = sanitize_hex_color(wp_unslash($_POST['matrix_effect_options']['text_bg_color_rgb'])) ?: '#000000';
        $a = isset($_POST['matrix_effect_options']['text_bg_color_a']) ?
            max(0, min(1, floatval(wp_unslash($_POST['matrix_effect_options']['text_bg_color_a'])))) :
            0.7;
        $r = hexdec(substr($rgb, 1, 2));
        $g = hexdec(substr($rgb, 3, 2));
        $b = hexdec(substr($rgb, 5, 2));
        $new_options['text_bg_color'] = "rgba($r, $g, $b, $a)";
    }
        if (isset($_POST['matrix_effect_options']['word_effect_size'])) {
            $size = intval($_POST['matrix_effect_options']['word_effect_size']);
            $new_options['word_effect_size'] = max(12, min(48, $size));
        }
        if (isset($_POST['matrix_effect_options']['text_color'])) {
           $new_options['text_color'] = sanitize_hex_color(wp_unslash($_POST['matrix_effect_options']['text_color'])) ?: '#00ff41';
        }
        if (isset($_POST['matrix_effect_options']['word_effect_frequency'])) {
            $frequency = intval($_POST['matrix_effect_options']['word_effect_frequency']);
            $new_options['word_effect_frequency'] = max(0, min(60, $frequency));
        }
        if (isset($_POST['matrix_effect_options']['cursor_style'])) {
        $allowed_styles = ['blinking', 'static', 'underline'];
        $style = sanitize_text_field(
            wp_unslash($_POST['matrix_effect_options']['cursor_style'])
        );
        $new_options['cursor_style'] = in_array($style, $allowed_styles) ? $style : 'blinking';
        }

        if (isset($_POST['matrix_effect_options']['cursor_color'])) {
            $new_options['cursor_color'] = sanitize_hex_color(
                wp_unslash($_POST['matrix_effect_options']['cursor_color'])
            ) ?: '#00ff41';
        }
        if (isset($_POST['matrix_effect_options']['cursor_blink_speed'])) {
        $speed = is_numeric($_POST['matrix_effect_options']['cursor_blink_speed']) ? intval($_POST['matrix_effect_options']['cursor_blink_speed']) : 1000;
        $new_options['cursor_blink_speed'] = max(100, min(3000, $speed));
        }
        if (isset($_POST['matrix_effect_options']['disable_on_pages'])) {
            $new_options['disable_on_pages'] = sanitize_text_field(
                wp_unslash($_POST['matrix_effect_options']['disable_on_pages'])
            );
        }
        if (isset($_POST['matrix_effect_options']['disable_on_post_types'])) {
            $new_options['disable_on_post_types'] = array_map(
                'sanitize_key',
                (array)$_POST['matrix_effect_options']['disable_on_post_types']
            );
        }
        return $new_options;
    }
    public function settings_init() {
            register_setting(
                'matrixEffectSettings',
                'matrix_effect_options',
                array($this, 'sanitize_matrix_settings')
            );
        add_settings_section(
            'matrix_effect_section',
            __('Matrix Effect Settings', 'matrix-effect'),
            [$this, 'settings_section_callback'],
            'matrix-effect-settings'
        );
        add_settings_field(
            'text_color',
            __('Text Color', 'matrix-effect'),
            [$this, 'color_field_render'],
            'matrix-effect-settings',
            'matrix_effect_section',
            [
                'field' => 'text_color',
                'default' => '#00ff41',
                'description' => __('Select the text color for matrix effect', 'matrix-effect')
            ]
        );
        add_settings_field(
            'animation_speed',
            __('Animation speed (1-10)', 'matrix-effect'),
            [$this, 'number_field_render'],
            'matrix-effect-settings',
            'matrix_effect_section',
            ['field' => 'animation_speed', 'default' => 5 ,'description' => __('The higher the number, the higher the speed.', 'matrix-effect')]
        );
        add_settings_field(
            'enable_pause',
            __('Turn on pause','matrix-effect'),
            [$this, 'checkbox_field_render'],
            'matrix-effect-settings',
            'matrix_effect_section',
            ['field' => 'enable_pause', 'default' => 1 , 'description' => __('By turning it on you will be able to stop the matrix effect.', 'matrix-effect')]
        );
        add_settings_field(
            'typing_speed',
            __('Print speed (ms)','matrix-effect'),
            [$this, 'number_field_render'],
            'matrix-effect-settings',
            'matrix_effect_section',
            ['field' => 'typing_speed', 'default' => 30, 'min' => 10, 'max' => 200 , 'description' => __('1 - the highest speed', 'matrix-effect')]
        );
        add_settings_field(
            'falling_font_size',
            __('Font size of falling characters (px)','matrix-effect'),
            [$this, 'number_field_render'],
            'matrix-effect-settings',
            'matrix_effect_section',
            [
                'field' => 'falling_font_size',
                'default' => 20,
                'min' => 10,
                'max' => 40,
                'description' => __('Font size for falling matrix symbols','matrix-effect')
            ]
        );
        add_settings_field(
            'chars_opacity',
            __('Transparency of symbols (0.1-1)','matrix-effect'),
            [$this, 'number_field_render'],
            'matrix-effect-settings',
            'matrix_effect_section',
            [
                'field' => 'chars_opacity',
                'default' => 0.8,
                'min' => 0.1,
                'max' => 1,
                'step' => 0.1,
                'description' => __('Set the transparency of the falling symbols(1 - fully opaque)','matrix-effect')]
        );
        add_settings_field(
            'text_bg_color',
            __('Text block background color','matrix-effect'),
            [$this, 'color_field_render'],
            'matrix-effect-settings',
            'matrix_effect_section',
            [
                'field' => 'text_bg_color',
                'default' => 'rgba(0, 0, 0, 0.7)',
                'description' => __('Select a background color for the text block','matrix-effect')
            ]
        );
        add_settings_field(
            'cursor_style',
            __('Cursor style', 'matrix-effect'),
            [$this, 'select_field_render'],
            'matrix-effect-settings',
            'matrix_effect_section',
            [
                'field' => 'cursor_style',
                'options' => [
                    'blinking'  => __('Blinking', 'matrix-effect'),
                    'static'    => __('Static', 'matrix-effect'),
                    'underline' => __('Underline', 'matrix-effect')
                ],
                'default' => 'blinking',
                'description' => __('What will your cursor look like','matrix-effect')
            ]
        );
        add_settings_field(
            'cursor_blink_speed',
            __('Flashing speed (ms)', 'matrix-effect'),
            [$this, 'number_field_render'],
            'matrix-effect-settings',
            'matrix_effect_section',
            [
                'field' => 'cursor_blink_speed',
                'default' => 1000,
                'min' => 100,
                'max' => 3000,
                'step' => 100,
                'description' => __('1 - very fast cursor blinking speed','matrix-effect')
            ]
        );
        add_settings_field(
            'word_effect_frequency',
            __('Word frequency (sec)', 'matrix-effect'),
            [$this, 'number_field_render'],
            'matrix-effect-settings',
            'matrix_effect_section',
            [
                'field' => 'word_effect_frequency',
                'default' => 15,
                'min' => 5,
                'max' => 60,
                'step' => 5,
                'description' => __('Word appearance interval (0 - disable effect)','matrix-effect')
            ]
        );
        add_settings_field(
            'word_effect_size',
            __('Word size (px)', 'matrix-effect'),
            [$this, 'number_field_render'],
            'matrix-effect-settings',
            'matrix_effect_section',
            [
                'field' => 'word_effect_size',
                'default' => 24,
                'min' => 4,
                'max' => 48,
                'description' => __('Size of words appearing','matrix-effect')
            ]
        );
        add_settings_field(
            'disable_on_pages',
            __('Disable on pages', 'matrix-effect'),
            [$this, 'text_field_render'],
            'matrix-effect-settings',
            'matrix_effect_section',
            [
                'field' => 'disable_on_pages',
                'default' => '',
                'description' => __('Page IDs separated by commas where to disable the effect','matrix-effect')
            ]
        );
        add_settings_field(
            'disable_on_post_types',
            __('Disable for post types', 'matrix-effect'),
            [$this, 'checkbox_group_render'],
            'matrix-effect-settings',
            'matrix_effect_section',
            [
                'field' => 'disable_on_post_types',
                'options' => $this->get_post_types_options(),
                'description' => __('Where not to show the effect','matrix-effect')
            ]
        );
        add_settings_field(
            'enable_selective_mode',
            __('Selective switching mode', 'matrix-effect'),
            [$this, 'checkbox_field_render'],
            'matrix-effect-settings',
            'matrix_effect_section',
            [
                'field' => 'enable_selective_mode',
                'default' => 0,
                'description' => __('If enabled, the effect will ONLY work on the pages below','matrix-effect')
            ]
        );
        add_settings_field(
            'enabled_pages',
            __('Pages to enable effect', 'matrix-effect'),
            [$this, 'text_field_render'],
            'matrix-effect-settings',
            'matrix_effect_section',
            [
                'field' => 'enabled_pages',
                'default' => '',
                'description' => __('Page IDs separated by commas (for example: 5,12,18)','matrix-effect'),
                'placeholder' => __('example: 5,12,18','matrix-effect')
            ]
        );
    }
private function get_post_types_options() {
    $post_types = get_post_types(['public' => true], 'objects');
    $options = [];
    foreach ($post_types as $post_type) {
        $options[$post_type->name] = $post_type->label;
    }
    return $options;
}
public function text_field_render($args) {
    $options = get_option('matrix_effect_options', []);
    $value = $options[$args['field']] ?? $args['default'];
    $data_attr = '';
    if (in_array($args['field'], ['enabled_pages', 'disable_on_pages'])) {
        $data_attr = 'data-matrix-field="pages"';
    }
    printf(
        '<input type="text" name="matrix_effect_options[%s]" value="%s" class="regular-text" %s>',
        esc_attr($args['field']),
        esc_attr($value),
        esc_attr($data_attr)
    );
    if (!empty($args['description'])) {
        printf('<p class="description">%s</p>', esc_html($args['description']));
    }
}
public function load_textdomain() {
    add_action('init', function() {
        load_plugin_textdomain('matrix-effect');
    });
}
public function checkbox_group_render($args) {
    $options = get_option('matrix_effect_options');
    $saved_values = isset($options[$args['field']]) ? (array)$options[$args['field']] : [];
    foreach ($args['options'] as $value => $label) {
        $checked = in_array($value, $saved_values) ? 'checked="checked"' : '';
        echo '<label><input type="checkbox" name="matrix_effect_options['.esc_attr($args['field']).'][]"
              value="'.esc_attr($value).'" '.esc_attr($checked).'> '.esc_html($label).'</label><br>';
    }
    if (isset($args['description'])) {
        echo '<p class="description">'.esc_html($args['description']).'</p>';
    }
}
public function select_field_render($args) {
    $options = get_option('matrix_effect_options');
    $value = $options[$args['field']] ?? $args['default'];
    echo '<select name="matrix_effect_options['.esc_attr($args['field']).']">';
    foreach ($args['options'] as $key => $label) {
        echo '<option value="'.esc_attr($key).'" '.selected($key, $value, false).'>'.esc_html($label).'</option>';
    }
    echo '</select>';
    if (isset($args['description'])) {
        echo '<p class="description">'.esc_html($args['description']).'</p>';
    }
}
public function color_field_render($args) {
        $options = get_option('matrix_effect_options');
        $value = $options[$args['field']] ?? $args['default'];
        if ($args['field'] === 'text_color') {
            echo '<input type="color" name="matrix_effect_options['.esc_attr($args['field']).']" value="'.esc_attr($value).'">';
            if (isset($args['description'])) {
                echo '<p class="description">'.esc_html($args['description']).'</p>';
            }
            return;
        }
        preg_match('/rgba\((\d+),\s*(\d+),\s*(\d+),\s*([\d.]+)\)/', $value, $matches);
        $r = $matches[1] ?? 0;
        $g = $matches[2] ?? 0;
        $b = $matches[3] ?? 0;
        $a = $matches[4] ?? 0.7;
        $hex = sprintf("#%02x%02x%02x", $r, $g, $b);
        echo '<div class="color-picker-rgba">';
        echo '<input type="color" name="matrix_effect_options['.esc_attr($args['field']).'_rgb]" value="'.esc_attr($hex).'">';
        //echo '<span>Прозрачность: </span>';
        echo '<span>' . esc_html__('Transparency:', 'matrix-effect') . '</span>';

        echo '<input type="range" name="matrix_effect_options['.esc_attr($args['field']).'_a]" min="0" max="1" step="0.1" value="'.esc_attr($a).'">';
        echo '</div>';

        if (isset($args['description'])) {
            echo '<p class="description">'.esc_html($args['description']).'</p>';
        }
    }
public function number_field_render($args) {
        $options = get_option('matrix_effect_options');
        $value = $options[$args['field']] ?? $args['default'];
        $min = isset($args['min']) ? 'min="' . esc_attr($args['min']) . '"' : '';
        $max = isset($args['max']) ? 'max="' . esc_attr($args['max']) . '"' : '';
        $step = isset($args['step']) ? 'step="' . esc_attr($args['step']) . '"' : 'step="1"';
        echo '<input type="number"
                    name="matrix_effect_options[' . esc_attr($args['field']) . ']"
                    value="' . esc_attr($value) . '"
                    ' . esc_attr($min). '
                    ' . esc_attr($max) . '
                    ' . esc_attr($step) . '
                    class="small-text">';
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
public function checkbox_field_render($args) {
    $options = get_option('matrix_effect_options', []);
    $value = isset($options[$args['field']]) ? $options[$args['field']] : $args['default'];

    // Выводим чекбокс и пояснение в одной строке
    echo '<label style="display: flex; align-items: center; gap: 8px;">';
    echo '<input type="checkbox" name="matrix_effect_options['.esc_attr($args['field']).']" value="1" '.checked(1, $value, false).'>';
    if ($args['field'] === 'enable_selective_mode') {
        echo '<span style="color:#555">' . esc_html__(
            'The effect will ONLY work on the specified pages (ignoring other settings). If you clicked the checkbox and did not specify the page ID, the effect will be disabled on all pages.',
            'matrix-effect'
        ) . '</span>';
    } else {
        echo '<span style="color:#555">' . esc_html($args['description'] ?? '') . '</span>';
    }
    echo '</label>';
}
public function settings_section_callback() {
        echo '<p>' . esc_html__('Customize the appearance and behavior of the Matrix Effect','matrix-effect') . '</p>';
    }
public function add_admin_menu() {
        add_options_page(
            'Matrix Effect Settings',
            'Matrix Effect',
            'manage_options',
            'matrix-effect-settings',
            array($this, 'options_page_html')
        );
    }
public function options_page_html() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Matrix Effect Settings', 'matrix-effect') . '</h1>';
    echo '<form action="' . esc_url('options.php') . '" method="post">';
    settings_fields('matrixEffectSettings');
    do_settings_sections('matrix-effect-settings');
    submit_button(__('Save Settings', 'matrix-effect'));
    echo '</form></div>';
}
public function enqueue_assets() {
    if ($this->should_disable_effect()) {
        return;
    }
    if (is_single() || is_page()) {
        $options = get_option('matrix_effect_options');
        $options['text_color'] = $options['text_color'] ?? '#00ff41';
        $options['text_bg_color'] = $options['text_bg_color'] ?? 'rgba(0, 0, 0, 0.7)';
        wp_enqueue_style(
            'matrix-effect-style',
            plugin_dir_url(__FILE__) . 'matrix-effect.css',
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'matrix-effect.css')
        );
        wp_enqueue_script(
            'matrix-effect-script',
            plugin_dir_url(__FILE__) . 'matrix-effect.js',
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'matrix-effect.js'),
            true
        );

        wp_localize_script('matrix-effect-script', 'matrixEffectData', [
            'content' => wp_strip_all_tags(get_the_content()),
            'settings' => wp_parse_args($options, [
                'word_effect_size' => 24,

            ])
        ]);
        wp_localize_script('matrix-effect-admin', 'matrixEffectAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
           'nonce' => wp_create_nonce('matrix_effect_nonce')
        ]);
    }
}
/**
 * @return bool True, False
 */
private function should_disable_effect() {
    $options = get_option('matrix_effect_options', []);
    $current_id = get_the_ID();
    $post_type = get_post_type();
    if (!empty($options['enable_selective_mode'])) {
        $enabled_pages = isset($options['enabled_pages']) ?
            array_map('intval', explode(',', $options['enabled_pages'])) : [];

        if (!in_array($current_id, $enabled_pages)) {
            return true;
        }
    }
    $disabled_pages = isset($options['disable_on_pages']) ?
        array_map('intval', explode(',', $options['disable_on_pages'])) : [];

    if (in_array($current_id, $disabled_pages)) {
        return true;
    }
    $disabled_post_types = isset($options['disable_on_post_types']) ?
        (array)$options['disable_on_post_types'] : [];

    if (in_array($post_type, $disabled_post_types)) {
        return true;
    }
    $disabled_templates = [
        'template-registration.php',
        'template-login.php'
    ];
    foreach ($disabled_templates as $template) {
        if (is_page_template($template)) {
            return true;
        }
    }
    $disabled_body_classes = [
        'registration-page',
        'login-page'
    ];
    if (is_page()) {
        foreach ($disabled_body_classes as $class) {
            if (in_array($class, get_body_class())) {
                return true;
            }
        }
    }
    return (bool) apply_filters('matrix_effect_force_disable', false, $current_id, $post_type);
}
public function custom_disable_checks($disable) {
        if ($disable) return $disable;
        if (function_exists('is_woocommerce') && is_woocommerce()) {
            return true;
        }
        return $disable;
    }
public function add_html_structure() {
        if ($this->should_disable_effect()) {
            return;
        }
        if (is_single() || is_page()) {
            echo '
            <style>
                .entry-content, .post-content, .wp-block-post-content { display: none !important; }
                .matrix-content-wrapper { min-height: 70vh; position: relative; }
            </style>
            <div class="matrix-content-wrapper">
                <div id="matrixEffectContainer"></div>
                <div id="finalTextContainer"></div>
                <div id="pauseOverlay">Нажмите для продолжения...</div>
            </div>
            ';
        }
    }
}

new MatrixEffectPlugin();
