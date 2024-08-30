<?php
/**
 * Plugin Name: WP URL Shortener Rotator
 * Description: Automatically shortens post links using multiple custom shorteners and rotates the shortened links on user clicks.
 * Version: 1.5
 * Author: Mr_godfather9
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/class-url-shortener.php';

// Enqueue admin styles
add_action('admin_enqueue_scripts', 'wp_url_shortener_rotator_admin_styles');
function wp_url_shortener_rotator_admin_styles() {
    wp_enqueue_style('wp-url-shortener-rotator-admin', plugin_dir_url(__FILE__) . 'assets/css/admin-styles.css');
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
    <div class="wrap">
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

    add_settings_field(
        'api_token_seturl',
        'SetURL API Token',
        'wp_url_shortener_rotator_api_token_seturl_render',
        'wp-url-shortener-settings',
        'wp_url_shortener_rotator_section'
    );

    add_settings_field(
        'api_token_custom2',
        'Linkshortify API Token',
        'wp_url_shortener_rotator_api_token_custom2_render',
        'wp-url-shortener-settings',
        'wp_url_shortener_rotator_section'
    );
}

function wp_url_shortener_rotator_section_callback() {
    echo 'Enter your API tokens for the custom URL shortener services.';
}

function wp_url_shortener_rotator_api_token_seturl_render() {
    $options = get_option('wp_url_shortener_rotator_options');
    ?>
    <input type="text" name="wp_url_shortener_rotator_options[api_token_seturl]" value="<?php echo esc_attr($options['api_token_seturl']); ?>" style="width: 400px;" />
    <?php
}

function wp_url_shortener_rotator_api_token_custom2_render() {
    $options = get_option('wp_url_shortener_rotator_options');
    ?>
    <input type="text" name="wp_url_shortener_rotator_options[api_token_custom2]" value="<?php echo esc_attr($options['api_token_custom2']); ?>" style="width: 400px;" />
    <?php
}

function sanitize_api_tokens($input) {
    $new_input = array();
    if (isset($input['api_token_seturl'])) {
        $new_input['api_token_seturl'] = sanitize_text_field($input['api_token_seturl']);
    }
    if (isset($input['api_token_custom2'])) {
        $new_input['api_token_custom2'] = sanitize_text_field($input['api_token_custom2']);
    }
    return $new_input;
}

// Append shortened links to post content with custom short URL
function append_shortened_links($content) {
    global $post;

    $options = get_option('wp_url_shortener_rotator_options');
    $api_token_seturl = isset($options['api_token_seturl']) ? $options['api_token_seturl'] : '';
    $api_token_custom2 = isset($options['api_token_custom2']) ? $options['api_token_custom2'] : '';

    $url_shortener = new URL_Shortener($api_token_seturl, $api_token_custom2);

    // Refined regular expression to match only URLs within href attributes
    preg_match_all('/<a[^>]+href=["\'](https?:\/\/[^"\']+)["\'][^>]*>/i', $content, $matches);
    $urls = $matches[1];

    if (!empty($urls)) {
        foreach ($urls as $url) {
            // Check if the URL is already shortened and cached
            $short_code = $url_shortener->get_short_code($url);
            
            // If not cached, shorten the URL and cache it
            if (!$short_code) {
                $short_code = $url_shortener->shorten_url($url);
            }

            if ($short_code) {
                // Replace the original URL with the custom short URL in the content
                $custom_short_url = home_url('/?id=' . $short_code);
                $content = str_replace($url, $custom_short_url, $content);
            }
        }
    }

    return $content;
}

add_filter('the_content', 'append_shortened_links');

// Handle redirection based on custom short URL and rotate between shortened links
function handle_redirect() {
    if (isset($_GET['id'])) {
        $short_code = sanitize_text_field($_GET['id']);
        $url_shortener = new URL_Shortener();
        $rotated_url = $url_shortener->get_rotated_url($short_code);

        if ($rotated_url) {
            // Redirect to the rotated shortened URL
            wp_redirect($rotated_url);
            exit;
        } else {
            wp_die('Invalid URL');
        }
    }
}

add_action('template_redirect', 'handle_redirect');
