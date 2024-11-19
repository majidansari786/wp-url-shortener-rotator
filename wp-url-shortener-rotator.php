<?php
/**
 * Plugin Name: WP URL Shortener Rotator
 * Description: Automatically shortens post links using multiple custom shorteners and rotates the shortened links on user clicks.
 * Version: 1.8
 * Author: Mr_godfather9
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/class-url-shortener.php';

// Enqueue admin styles only on the plugin's settings page
add_action('admin_enqueue_scripts', 'wp_url_shortener_rotator_admin_styles');
function wp_url_shortener_rotator_admin_styles($hook_suffix) {
    if ($hook_suffix == 'settings_page_wp-url-shortener-settings') {
        wp_enqueue_style('wp-url-shortener-rotator-admin', plugin_dir_url(__FILE__) . 'assets/css/admin-styles.css');
    }
}

// Activation hook to create the database table
register_activation_hook(__FILE__, 'wp_url_shortener_rotator_install');
function wp_url_shortener_rotator_install() {
    $url_shortener = new URL_Shortener();
    $url_shortener->install();
}

// Add settings menu
add_action('admin_menu', 'wp_url_shortener_rotator_menu');
function wp_url_shortener_rotator_menu() {
    add_options_page(
        'URL Shortener Settings',
        'URL Shortener',
        'manage_options',
        'wp-url-shortener-settings',
        'wp_url_shortener_rotator_settings_page'
    );
}

// Render the settings page
function wp_url_shortener_rotator_settings_page() {
    ?>
    <div class="wrap wp-url-shortener-settings-page">
        <h1>URL Shortener Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wp_url_shortener_rotator_options');
            do_settings_sections('wp-url-shortener-settings');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

// Register settings and fields
add_action('admin_init', 'wp_url_shortener_rotator_settings_init');
function wp_url_shortener_rotator_settings_init() {
    register_setting('wp_url_shortener_rotator_options', 'wp_url_shortener_rotator_options', 'sanitize_api_tokens');

    add_settings_section(
        'wp_url_shortener_rotator_section',
        'Custom Shortener API Settings',
        'wp_url_shortener_rotator_section_callback',
        'wp-url-shortener-settings'
    );

    $fields = [
        'api_token_seturl' => 'Seturl API Token',
        'api_token_custom2' => 'Linkshortify API Token',
        'api_token_modijiurl' => 'ModijiURL API Token',
        'api_token_publicearn' => 'PublicEarn API Token',
        'api_token_urlshortx' => 'UrlShortX API Token',
        'api_token_atglinks' => 'ATGLinks API Token'
    ];

    foreach ($fields as $field => $label) {
        add_settings_field(
            $field,
            $label,
            function () use ($field) {
                $options = get_option('wp_url_shortener_rotator_options');
                ?>
                <input type="text" name="wp_url_shortener_rotator_options[<?php echo $field; ?>]" 
                       value="<?php echo esc_attr($options[$field] ?? ''); ?>" 
                       style="width: 400px;" />
                <?php
            },
            'wp-url-shortener-settings',
            'wp_url_shortener_rotator_section'
        );
    }
}

function wp_url_shortener_rotator_section_callback() {
    echo 'Enter your API tokens for the custom URL shortener services.';
}

function sanitize_api_tokens($input) {
    $new_input = [];
    $allowed_keys = [
        'api_token_seturl',
        'api_token_custom2',
        'api_token_modijiurl',
        'api_token_publicearn',
        'api_token_urlshortx',
        'api_token_atglinks'
    ];

    foreach ($allowed_keys as $key) {
        if (isset($input[$key])) {
            $new_input[$key] = sanitize_text_field($input[$key]);
        }
    }
    return $new_input;
}

// Append shortened links to post content
function append_shortened_links($content) {
    $options = get_option('wp_url_shortener_rotator_options', []);
    $url_shortener = new URL_Shortener(
        $options['api_token_seturl'] ?? '',
        $options['api_token_custom2'] ?? '',
        $options['api_token_modijiurl'] ?? '',
        $options['api_token_publicearn'] ?? '',
        $options['api_token_urlshortx'] ?? '',
        $options['api_token_atglinks'] ?? ''
    );

    preg_match_all('/<a[^>]+href=["\'](https?:\/\/[^"\']+)["\'][^>]*>/i', $content, $matches);
    $urls = $matches[1];

    if (!empty($urls)) {
        foreach ($urls as $url) {
            $short_code = $url_shortener->get_short_code($url);
            if (!$short_code) {
                $short_code = $url_shortener->shorten_url($url);
            }

            if ($short_code) {
                $custom_short_url = home_url('/?id=' . $short_code);
                $content = str_replace($url, $custom_short_url, $content);
            }
        }
    }

    return $content;
}

add_filter('the_content', 'append_shortened_links');

// Handle redirection based on custom short URL
function handle_redirect() {
    if (isset($_GET['id'])) {
        $short_code = sanitize_text_field($_GET['id']);
        $url_shortener = new URL_Shortener();
        $rotated_url = $url_shortener->get_rotated_url($short_code);

        if ($rotated_url) {
            wp_redirect($rotated_url);
            exit;
        } else {
            wp_die('Invalid URL');
        }
    }
}

add_action('template_redirect', 'handle_redirect');
