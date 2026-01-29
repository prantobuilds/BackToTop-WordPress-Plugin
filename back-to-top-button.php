<?php
/**
 * Plugin Name: Back To Top Button
 * Description: A simple plugin to add a back-to-top button with smooth scroll and customizable settings.
 * Version: 1.2.1
 * Author: Samiul H Pranto 
 * Author URI: https://profile.wordpress.org/samiulhpranto
 * Text Domain: bttb-plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Sanitization: Clean data before it hits the database
 */
function bttb_sanitize_settings($input)
{
    $sanitized = array();

    // Sanitize Scroll Distance (Numeric)
    if (isset($input['scroll_dist'])) {
        $sanitized['scroll_dist'] = absint($input['scroll_dist']);
    }

    // Sanitize Color
    if (isset($input['color'])) {
        $sanitized['color'] = sanitize_hex_color($input['color']);
    }

    // Sanitize Position
    if (isset($input['position'])) {
        $sanitized['position'] = ($input['position'] === 'left') ? 'left' : 'right';
    }

    // Sanitize Shape
    if (isset($input['shape'])) {
        $valid_shapes = array('square', 'rounded', 'circle');
        $sanitized['shape'] = in_array($input['shape'], $valid_shapes) ? $input['shape'] : 'circle';
    }

    // Sanitize Z-Index
    if (isset($input['z_index'])) {
        $sanitized['z_index'] = intval($input['z_index']);
    }

    // Sanitize Button Size
    if (isset($input['size'])) {
        $sanitized['size'] = absint($input['size']);
    }

    // Sanitize Icon Choice
    if (isset($input['icon'])) {
        $valid_icons = array('dashicons-arrow-up', 'dashicons-arrow-up-alt', 'dashicons-arrow-up-alt2', 'dashicons-upload');
        $sanitized['icon'] = in_array($input['icon'], $valid_icons) ? $input['icon'] : 'dashicons-arrow-up-alt';
    }

    return $sanitized;
}

/**
 * 2. Enqueue assets with optimized inline CSS
 */
function bttb_enqueue_assets()
{
    $options = get_option('bttb_settings');

    wp_enqueue_style('bttb-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.2');
    wp_enqueue_script('bttb-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.2', true);
    wp_enqueue_style('dashicons');

    // Fallbacks
    $color = !empty($options['color']) ? $options['color'] : '#333333';
    $side = (isset($options['position']) && $options['position'] === 'left') ? 'left' : 'right';
    $shape = !empty($options['shape']) ? $options['shape'] : 'circle';
    $z_index = !empty($options['z_index']) ? $options['z_index'] : 9999;
    $size = !empty($options['size']) ? $options['size'] : 45;
    $font_size = round($size * 0.5); // Dynamic arrow size

    // Map shapes to border-radius values
    $radius = '50%'; // default circle
    if ($shape === 'square')
        $radius = '0px';
    if ($shape === 'rounded')
        $radius = '8px';

    // Single CSS declaration block
    $custom_css = "
        #back-to-top {
            background-color: " . esc_attr($color) . " !important;
            " . esc_attr($side) . ": 30px;
            border-radius: " . esc_attr($radius) . ";
            z-index: " . intval($z_index) . ";
            width: " . intval($size) . "px;
            height: " . intval($size) . "px;
            font-size: " . intval($font_size) . "px;
        }
    ";
    wp_add_inline_style('bttb-style', $custom_css);

    // Pass data to JS
    $scroll_dist = !empty($options['scroll_dist']) ? $options['scroll_dist'] : 300;
    wp_localize_script('bttb-script', 'bttb_vars', array(
        'scroll_dist' => $scroll_dist
    ));
}
add_action('wp_enqueue_scripts', 'bttb_enqueue_assets');

/**
 * 3. Add Button Markup
 */
// function bttb_add_button()
// {
//     echo '<button id="back-to-top" aria-label="Back to top" type="button">&#8679;</button>';
// }
// add_action('wp_footer', 'bttb_add_button');

function bttb_add_button()
{
    // 1. Get options carefully
    $options = get_option('bttb_settings', array());

    // 2. Set default icon if none exists
    $icon = (isset($options['icon']) && !empty($options['icon'])) ? $options['icon'] : 'dashicons-arrow-up-alt';

    // 3. Print the button
    ?>
        <button id="back-to-top" aria-label="Back to top" type="button">
            <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
        </button>
        <?php
}
// Ensure this is NOT inside another function or commented out
add_action('wp_footer', 'bttb_add_button');


/**
 * 4. Register Settings
 */
function bttb_register_settings()
{
    register_setting(
        'bttb_settings_group',
        'bttb_settings',
        array('sanitize_callback' => 'bttb_sanitize_settings')
    );
}
add_action('admin_init', 'bttb_register_settings');

/**
 * 5. Admin Settings Page
 */
function bttb_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $options = get_option('bttb_settings');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('bttb_settings_group');
            $color = $options['color'] ?? '#333333';
            $position = $options['position'] ?? 'right';
            $scroll_dist = $options['scroll_dist'] ?? '300';
            ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Button Shape</th>
                    <td>
                        <?php $shape = $options['shape'] ?? 'circle'; ?>
                        <select name="bttb_settings[shape]">
                            <option value="circle" <?php selected($shape, 'circle'); ?>>Circle</option>
                            <option value="rounded" <?php selected($shape, 'rounded'); ?>>Rounded Corners</option>
                            <option value="square" <?php selected($shape, 'square'); ?>>Square</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Scroll Distance (px)</th>
                    <td>
                        <input type="number" name="bttb_settings[scroll_dist]" value="<?php echo esc_attr($scroll_dist); ?>"
                            step="10">
                        <p class="description">How far down the user scrolls before the button appears.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Button Color</th>
                    <td>
                        <input type="color" name="bttb_settings[color]" value="<?php echo esc_attr($color); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Position</th>
                    <td>
                        <select name="bttb_settings[position]">
                            <option value="right" <?php selected($position, 'right'); ?>>Right</option>
                            <option value="left" <?php selected($position, 'left'); ?>>Left</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Z-Index</th>
                    <td>
                        <input type="number" name="bttb_settings[z_index]"
                            value="<?php echo esc_attr($options['z_index'] ?? '9999'); ?>">
                        <p class="description">Higher numbers keep the button on top of other elements (Default: 9999).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Button Size (px)</th>
                    <td>
                        <input type="number" name="bttb_settings[size]"
                            value="<?php echo esc_attr($options['size'] ?? '45'); ?>" min="20" max="100">
                        <p class="description">Standard size is 45px.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Button Icon</th>
                    <td>
                        <?php $selected_icon = $options['icon'] ?? 'dashicons-arrow-up-alt'; ?>
                        <select name="bttb_settings[icon]">
                            <option value="dashicons-arrow-up" <?php selected($selected_icon, 'dashicons-arrow-up'); ?>>Thin
                                Arrow</option>
                            <option value="dashicons-arrow-up-alt" <?php selected($selected_icon, 'dashicons-arrow-up-alt'); ?>>Solid Arrow</option>
                            <option value="dashicons-arrow-up-alt2" <?php selected($selected_icon, 'dashicons-arrow-up-alt2'); ?>>Circle Arrow</option>
                            <option value="dashicons-upload" <?php selected($selected_icon, 'dashicons-upload'); ?>>Upload
                                Style</option>
                        </select>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function bttb_add_admin_menu()
{
    add_menu_page(
        'Back To Top Settings',
        'Back To Top',
        'manage_options',
        'bttb-settings',
        'bttb_settings_page',
        'dashicons-arrow-up-alt',
        25
    );
}
add_action('admin_menu', 'bttb_add_admin_menu');

function bttb_plugin_settings_link($links)
{
    $settings_link = '<a href="admin.php?page=bttb-settings">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter("plugin_action_links_" . plugin_basename(__FILE__), 'bttb_plugin_settings_link');