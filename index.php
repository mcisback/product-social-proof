<?php
/**
 * Plugin Name: Product Social Proof
 * Description: A plugin that creates a shortcode to display dynamic product information using Faker and geolocation.
 * Version: 1.0
 * Author: Marco Caggiano
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ProductSocialProofPlugin {
    private $shortcode_name;
    private $js_handle;

    public function __construct() {
        $this->shortcode_name = 'product_social_proof';
        $this->js_handle = 'product-social-proof-script';

        add_action('init', [$this, "init"]);
        add_action('wp_enqueue_scripts', [$this, "enqueue_scripts"]);
    }

    public function init(): void {
        $this->get_locations_near_ip();

        add_shortcode($this->shortcode_name, [$this, "shortcode_callback"]);
    }

    public function get_names_from_locale(string $gender = '', string $locale = 'it_IT'): void {
        $quantity = 20;

        $url = "https://fakerapi.it/api/v2/persons?_locale=$locale&_gender=$gender&_quantity=$quantity";

        if($gender === '')  {
            $url = "https://fakerapi.it/api/v2/persons?_locale=$locale&_quantity=$quantity";
        }

        $response = wp_remote_get($url);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            throw new Exception("Invalid response from fakerapi.it: " . wp_remote_retrieve_response_message($response));
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        ?>
        <script type="application/json" id="product_social_proof_persons_names">
            <?php echo json_encode( $data["data"], JSON_UNESCAPED_SLASHES ); ?>
        </script>
        <?php
    }

    public function get_locations_near_ip(): void {
        $location = urlencode($this->get_user_location_from_ip());

        if($location) {
            $geo_url = "http://geodb-free-service.wirefreethought.com/v1/geo/places?limit=10&offset=0&types=CITY&location=$location&languageCode=it";

            $response = wp_remote_get($geo_url, ['timeout' => 10]);
    
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
                throw new Exception("geodb-free-service unavailable: " . wp_remote_retrieve_response_message($response));
            }
    
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
    
            add_action( 'wp_head', function() use($data) {
                ?>
                <script type="application/json" id="product_social_proof_geos">
                    <?php echo json_encode( $data["data"], JSON_UNESCAPED_SLASHES ); ?>
                </script>
                <?php
            });
        }
    }

    public function enqueue_scripts(): void {
        wp_enqueue_script(
            $this->js_handle,
            plugin_dir_url(__FILE__) . 'js/socialproof.js',
            [],
            '1.5',
            true
        );
    }

    public function shortcode_callback(array $atts): string {
        ob_start();
        
        $attributes = shortcode_atts([
            'product' => '',
            'interval' => '2000',
            'gender' => ''
        ], $atts, $this->shortcode_name);

        $product = sanitize_text_field($attributes['product']);
        $interval = sanitize_text_field($attributes['interval']);
        $gender = sanitize_text_field($attributes['gender']);

        $this->get_names_from_locale($gender);

        echo sprintf(
            '<div class="product-social-proof" data-product="%s" data-interval="%s" data-gender="%s"></div>',
            esc_attr($product),
            esc_attr($interval),
            esc_attr($gender)
        );

        return ob_get_clean();
    }

    private function get_user_location_from_ip(): string | null {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $api_url = "https://ipapi.co/{$ip_address}/json/";

        $response = wp_remote_get($api_url);

        // Better to log this error and notify the user, this exception breaks wordpress
        // if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
        //     throw new Exception("ipapi.co unavailable: " . wp_remote_retrieve_response_message($response));
        // }

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data['latitude'] . '+' . $data['longitude'];
    }
}

new ProductSocialProofPlugin();