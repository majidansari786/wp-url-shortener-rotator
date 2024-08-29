<?php
class URL_Shortener {

    private $table_name;
    private $api_token_seturl;
    private $api_token_custom2;

    public function __construct($api_token_seturl = '', $api_token_custom2 = '') {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'url_shortener';
        $this->api_token_seturl = $api_token_seturl;
        $this->api_token_custom2 = $api_token_custom2;
    }

    // Function to create the database table
    public function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            original_url text NOT NULL,
            short_code varchar(20) NOT NULL,
            shortened_url_seturl text NOT NULL,
            shortened_url_custom2 text NOT NULL,
            click_count bigint(20) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY short_code (short_code),
            KEY original_url (original_url(255))
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Generate a unique shortcode for the URL
    public function generate_short_code($url) {
        return substr(md5($url . time()), 0, 8);
    }

    // Retrieve the short code if the URL is already shortened
    public function get_short_code($url) {
        global $wpdb;

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT short_code FROM $this->table_name WHERE original_url = %s",
            $url
        ));

        return $result ? $result : false;
    }

    // Shorten the URL using both shorteners and store them in the database
    public function shorten_url($url) {
        global $wpdb;

        $short_code = $this->generate_short_code($url);

        // Shorten with SetURL
        $shortened_url_seturl = $this->shorten_with_api($url, $this->api_token_seturl, 'seturl');

        // Shorten with Custom Shortener 2
        $shortened_url_custom2 = $this->shorten_with_api($url, $this->api_token_custom2, 'custom2');

        if ($shortened_url_seturl && $shortened_url_custom2) {
            $wpdb->insert(
                $this->table_name,
                array(
                    'original_url' => $url,
                    'short_code' => $short_code,
                    'shortened_url_seturl' => $shortened_url_seturl,
                    'shortened_url_custom2' => $shortened_url_custom2,
                ),
                array(
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                )
            );

            return $short_code;
        }

        return false;
    }

    // Helper function to shorten the URL using the API
    private function shorten_with_api($url, $api_token, $shortener) {
        if (empty($api_token)) {
            return false;
        }

        $long_url = urlencode($url);
        $short_code = $this->generate_short_code($url);

        $api_url = '';

        if ($shortener === 'seturl') {
            $api_url = "https://seturl.in/api?api={$api_token}&url={$long_url}&alias={$short_code}";
        } elseif ($shortener === 'custom2') {
            // Replace with the actual API endpoint of the second shortener
            $api_url = "https://linkshortify/api?api={$api_token}&url={$long_url}&alias={$short_code}";
        }

        $result = @json_decode(file_get_contents($api_url), TRUE);

        if ($result["status"] === 'error') {
            return false;
        } else {
            return $result["shortenedUrl"];
        }
    }

    // Retrieve the correct shortened URL based on click count
    public function get_rotated_url($short_code) {
        global $wpdb;

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT shortened_url_seturl, shortened_url_custom2, click_count FROM $this->table_name WHERE short_code = %s",
            $short_code
        ));

        if ($result) {
            // Rotate between the two shortened URLs
            $next_url = $result->click_count % 2 == 0 ? $result->shortened_url_seturl : $result->shortened_url_custom2;

            // Increment the click count
            $wpdb->update(
                $this->table_name,
                array('click_count' => $result->click_count + 1),
                array('short_code' => $short_code),
                array('%d'),
                array('%s')
            );

            return $next_url;
        }

        return false;
    }
}
