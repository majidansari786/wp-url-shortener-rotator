<?php
class URL_Shortener {
    private $table_name;
    private $api_tokens;

    public function __construct(...$api_tokens) {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'url_shortener';
        $this->api_tokens = [
            'seturl' => $api_tokens[0] ?? '',
            'custom2' => $api_tokens[1] ?? '',
            'modijiurl' => $api_tokens[2] ?? '',
            'publicearn' => $api_tokens[3] ?? '',
            'urlshortx' => $api_tokens[4] ?? '',
            'atglinks' => $api_tokens[5] ?? ''
        ];
    }

    public function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            original_url text NOT NULL,
            short_code varchar(20) NOT NULL,
            shortened_url_seturl text NULL,
            shortened_url_custom2 text NULL,
            shortened_url_modijiurl text NULL,
            shortened_url_publicearn text NULL,
            shortened_url_urlshortx text NULL,
            shortened_url_atglinks text NULL,
            click_count bigint(20) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY short_code (short_code),
            KEY original_url (original_url(255))
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function shorten_url($url) {
        global $wpdb;

        $short_code = $this->generate_short_code($url);
        $data = ['original_url' => $url, 'short_code' => $short_code];

        foreach ($this->api_tokens as $shortener => $api_token) {
            if (!empty($api_token)) {
                $data["shortened_url_{$shortener}"] = $this->shorten_with_api($url, $api_token, $shortener);
            }
        }

        if (!empty(array_filter($data, fn($v) => str_starts_with($v, 'shortened_url_')))) {
            $wpdb->insert($this->table_name, $data);
            return $short_code;
        }

        return false;
    }

    private function shorten_with_api($url, $api_token, $shortener) {
        $long_url = urlencode($url);
        $short_code = $this->generate_short_code($url);

        $api_url = match ($shortener) {
            'seturl' => "https://seturl.in/api?api={$api_token}&url={$long_url}&alias={$short_code}",
            'custom2' => "https://linkshortify.com/api?api={$api_token}&url={$long_url}&alias={$short_code}",
            'modijiurl' => "https://modijiurl.com/api?api={$api_token}&url={$long_url}&alias={$short_code}",
            'publicearn' => "https://publicearn.com/api?api={$api_token}&url={$long_url}&alias={$short_code}",
            'urlshortx' => "https://urlshortx.com/api?api={$api_token}&url={$long_url}&alias={$short_code}",
            'atglinks' => "https://atglinks.com/api?api={$api_token}&url={$long_url}&alias={$short_code}",
            default => ''
        };

        $response = @file_get_contents($api_url);
        $result = json_decode($response, true);

        return $result['status'] === 'success' ? $result['shortenedUrl'] : null;
    }

    public function get_rotated_url($short_code) {
        global $wpdb;

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT shortened_url_seturl, shortened_url_custom2, shortened_url_modijiurl, shortened_url_publicearn, shortened_url_urlshortx, shortened_url_atglinks, click_count FROM $this->table_name WHERE short_code = %s",
            $short_code
        ));

        if ($result) {
            $urls = array_filter([
                $result->shortened_url_seturl,
                $result->shortened_url_custom2,
                $result->shortened_url_modijiurl,
                $result->shortened_url_publicearn,
                $result->shortened_url_urlshortx,
                $result->shortened_url_atglinks
            ]);
            $next_url = $urls[$result->click_count % count($urls)];

            $wpdb->update(
                $this->table_name,
                ['click_count' => $result->click_count + 1],
                ['short_code' => $short_code],
                ['%d']
            );

            return $next_url;
        }

        return false;
    }

    public function generate_short_code($url) {
        return substr(md5($url . time()), 0, 8);
    }
}
