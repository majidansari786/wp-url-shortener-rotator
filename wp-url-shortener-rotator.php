<?php
/**
 * Plugin Name: WP URL Shortener Rotator
 * Description: Automatically shortens post links using Seturl and ATGLinks and rotates them on user clicks.
 * Version: 2.0
 * Author: Mr_godfather9
 */

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-url-shortener.php';

register_activation_hook(__FILE__, function () {
    $url_shortener = new URL_Shortener();
    $url_shortener->install();
});

add_action('admin_menu', function () {
    add_options_page(
        'URL Shortener Settings',
        'URL Shortener',
        'manage_options',
        'wp-url-shortener-settings',
        'wp_url_shortener_rotator_settings_page'
    );
});

function wp_url_shortener_rotator_settings_page() {
    ?>
    <div class="wrap">
        <h1>URL Shortener Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wp_url_shortener_rotator_options');
            do_settings_sections('wp-url-shortener-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function () {
    register_setting('wp_url_shortener_rotator_options', 'wp_url_shortener_rotator_options', 'sanitize_api_tokens');

    add_settings_section(
        'wp_url_shortener_rotator_section',
        'Seturl & ATGLinks API Settings',
        fn() => print('Enter your API tokens:'),
        'wp-url-shortener-settings'
    );

    foreach (['seturl' => 'Seturl', 'atglinks' => 'ATGLinks'] as $key => $label) {
        add_settings_field(
            "api_token_{$key}",
            "$label API Token",
            function () use ($key) {
                $options = get_option('wp_url_shortener_rotator_options');
                ?>
                <input type="text" name="wp_url_shortener_rotator_options[api_token_<?php echo $key; ?>]" value="<?php echo esc_attr($options["api_token_{$key}"] ?? ''); ?>" style="width: 400px;" />
                <?php
            },
            'wp-url-shortener-settings',
            'wp_url_shortener_rotator_section'
        );
    }
});

function sanitize_api_tokens($input) {
    return [
        'api_token_seturl' => sanitize_text_field($input['api_token_seturl'] ?? ''),
        'api_token_atglinks' => sanitize_text_field($input['api_token_atglinks'] ?? '')
    ];
}

add_filter('the_content', function ($content) {
    $options = get_option('wp_url_shortener_rotator_options', []);
    $url_shortener = new URL_Shortener(
        $options['api_token_seturl'] ?? '',
        $options['api_token_atglinks'] ?? ''
    );

    preg_match_all('/<a[^>]+href=["\'](https?:\/\/[^"\']+)["\'][^>]*>/i', $content, $matches);
    $urls = $matches[1];

    if (!empty($urls)) {
        foreach ($urls as $url) {
            $short_code = $url_shortener->get_short_code($url) ?: $url_shortener->shorten_url($url);
            if ($short_code) {
                $custom_short_url = home_url('/?id=' . $short_code);
                $content = str_replace($url, esc_url($custom_short_url), $content);
            }
        }
    }

    return $content;
});

add_action('template_redirect', function () {
    if (isset($_GET['id'])) {
        $short_code = sanitize_text_field($_GET['id']);
        $url_shortener = new URL_Shortener();
        $rotated_url = $url_shortener->get_rotated_url($short_code);

        if ($rotated_url) {
            wp_redirect($rotated_url);
            exit;
        } else {
            wp_die('Invalid or expired short code.');
        }
    }
});
