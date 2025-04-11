<?php
/*
Plugin Name: Product Grid from links on Posts
Description: Create a grid of Amazon Products mentioned in post content using official PA-API.
Version: 1.0.2
Author: Fabio Ferrero
*/

if (!defined('ABSPATH')) exit;

class Amazon_Products_Grid {

    private $options;

    public function __construct() {
        add_shortcode('prodotti_amazon', [$this, 'render_shortcode']);
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_init', [$this, 'page_init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('wp_ajax_load_amazon_products', [$this, 'ajax_load_products']);
        add_action('wp_ajax_nopriv_load_amazon_products', [$this, 'ajax_load_products']);
    }

    public function enqueue_styles() {
        wp_enqueue_style('amazon-products-grid', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function render_shortcode() {
        global $post;
        
        // Get column settings from options
        $this->options = get_option('apg_settings');
        
        // Get individual column settings with defaults
        $desktop_cols = isset($this->options['desktop_columns']) ? intval($this->options['desktop_columns']) : 5;
        $tablet_cols = isset($this->options['tablet_columns']) ? intval($this->options['tablet_columns']) : 3;
        $mobile_cols = isset($this->options['mobile_columns']) ? intval($this->options['mobile_columns']) : 2;
        
        // Ensure numeric values and minimum of 1
        $desktop_cols = max(1, $desktop_cols);
        $tablet_cols = max(1, $tablet_cols);
        $mobile_cols = max(1, $mobile_cols);
        
        // Get text settings from options with defaults
        $heading_text = $this->options['heading_text'] ?? '🛒Prodotti menzionati nell\'articolo:';
        $subheading_text = $this->options['subheading_text'] ?? 'Acquistali su Amazon in un click';

        // Extract URLs from content
        $content = $post->post_content;
        preg_match_all('/https:\/\/amzn\.eu\/\S+/', $content, $matches);
        $urls = array_unique($matches[0]);
        
        if (empty($urls)) return '';
        
        // Enqueue Swiper scripts and styles with defer attribute
        wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css', array(), '10.0.0');
        wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js', array(), '10.0.0', true);
        
        // Generate unique ID for this slider instance
        $slider_id = 'amazon-products-slider-' . wp_rand();
        
        // Create a placeholder for the slider
        $html = '<div class="amazon-products-grid-wrapper">';
        $html .= '<h4>' . esc_html($heading_text) . '</h4>';
        $html .= '<p>' . esc_html($subheading_text) . '</p>';
        
        // Swiper container structure
        $html .= '<div class="swiper" id="' . esc_attr($slider_id) . '">';
        $html .= '<div class="swiper-wrapper">';
        $html .= '<div class="swiper-slide loading-slide"><div class="loading-spinner"></div></div>';
        $html .= '</div>'; // Close swiper-wrapper
        
        // Add pagination
        $html .= '<div class="swiper-pagination"></div>';
        
        $html .= '</div>'; // Close swiper container
        
                // Add custom inline CSS for the slider
                $html .= '<style>
                .amazon-products-grid-wrapper {
                    width: 100%;
                    margin: 30px 0;
                    padding: 20px 0;
                    background-color:rgb(248, 248, 248);
                    border-radius:1rem;
                }
                #' . esc_attr($slider_id) . ' {
                    padding: 3rem 1rem;

                }
                #' . esc_attr($slider_id) . ' .swiper-slide {
                    height: auto;
                    display: flex;
                    width: auto !important; /* Override Swiper\'s default width calculation */
                    flex: 1;
                    min-width: 200px; /* Minimum width for each product */
                    max-width: 300px; /* Maximum width for each product */
                }
                /* When fewer items, make them wider */
                #' . esc_attr($slider_id) . '.few-items .swiper-slide {
                    min-width: 250px;
                    max-width: 350px;
                }
               #' . esc_attr($slider_id) . ' .amazon-product {
                    padding: 10px;
                    text-align: center;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                    width: 100%;
                    background: #fff;
                    border-radius: 8px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                #' . esc_attr($slider_id) . ' .amazon-product a {
                    display: flex;
                    flex-direction: column;
                    height: 100%;
                    text-decoration: none;
                    color: inherit;
                }
                #' . esc_attr($slider_id) . ' .swiper-pagination-bullet-active {
                    background-color:#1d1d1d!important;
                }  
                #' . esc_attr($slider_id) . ' .amazon-product img {
                    max-width: 100%;
                    height: 180px; /* Fixed height for all images */
                    margin: 0 auto 10px;
                    object-fit: contain; /* Maintain aspect ratio without cropping */
                }
                #' . esc_attr($slider_id) . ' .amazon-product p {
                    font-size: 14px;
                    margin: 5px 0;
                    flex-grow: 1; /* Allow text area to expand */
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                #' . esc_attr($slider_id) . ' #amazon-logo {
                    width: 80px;
                    border-radius:0!important;
                    height: 30px;
                    margin: 0.5rem auto 0;
                    display: block;
                }
            </style>';
        
        // Add JavaScript to load products asynchronously
        // Add JavaScript to load products asynchronously
        $html .= '<script>
            document.addEventListener("DOMContentLoaded", function() {
                // Initialize empty Swiper first
                if (typeof Swiper !== "undefined") {
                    const swiper = new Swiper("#' . esc_js($slider_id) . '", {
                        slidesPerView: "auto", // Use auto instead of fixed number
                        spaceBetween: 20,
                        loop: false,
                        autoplay: {
                            delay: 3000,
                            disableOnInteraction: true,
                        },
                        pagination: {
                            el: "#' . esc_js($slider_id) . ' .swiper-pagination",
                            clickable: true,
                        },
                        breakpoints: {
                            768: {
                                slidesPerView: "auto", // Use auto for all breakpoints
                            },
                            992: {
                                slidesPerView: "auto",
                            }
                        }
                    });
                    
                    // Load products asynchronously
                    loadAmazonProducts(' . json_encode($urls) . ', "' . esc_js($slider_id) . '", swiper);
                }
            });
            
            function loadAmazonProducts(urls, sliderId, swiper) {
                const wrapper = document.querySelector("#" + sliderId + " .swiper-wrapper");
                const sliderElement = document.getElementById(sliderId);
                
                // Use fetch API to get product data via AJAX
                fetch("' . esc_js(admin_url('admin-ajax.php')) . '", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "action=load_amazon_products&urls=" + encodeURIComponent(JSON.stringify(urls))
                })
                .then(response => response.json())
                .then(data => {
                    // Remove loading slide
                    wrapper.innerHTML = "";
                    
                    // Add product slides
                    data.forEach(product => {
                        const slide = document.createElement("div");
                        slide.className = "swiper-slide";
                        
                        slide.innerHTML = `
                            <div class="amazon-product">
                                <a href="${product.url}" target="_blank" rel="nofollow">
                                    <img src="${product.image}" alt="${product.title}">
                                    <p>${product.title}</p>
                                    <img id="amazon-logo" src="https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg" alt="Amazon" class="amazon-logo">
                                </a>
                            </div>
                        `;
                        
                        wrapper.appendChild(slide);
                    });
                    
                    // Add class if fewer than 5 products
                    if (data.length < 5) {
                        sliderElement.classList.add("few-items");
                    }
                    
                    // Update Swiper
                    swiper.update();
                })
                .catch(error => {
                    console.error("Error loading Amazon products:", error);
                    wrapper.innerHTML = "<div class=\"swiper-slide\"><div class=\"amazon-product\">Error loading Amazon products</div></div>";
                });
            }
        </script>';
        
        $html .= '</div>'; // Close wrapper
        
        return $html;
    }
    
    // // AJAX handler for loading products
    public function ajax_load_products() {
        if (!isset($_POST['urls'])) {
            wp_send_json_error('No URLs provided');
        }
        
        $urls = json_decode(stripslashes($_POST['urls']), true);
        $products = [];
        
        foreach ($urls as $url) {
            $asin = $this->expand_and_extract_asin($url);
            if ($asin) {
                // Check if we have this product in transient cache
                $transient_key = 'apg_product_' . $asin;
                $cached_product = get_transient($transient_key);
                
                if ($cached_product !== false) {
                    // Use cached data
                    $products[] = $cached_product;
                } else {
                    // Fetch fresh data with retry mechanism
                    $product = $this->fetch_product_data_with_retry($asin, 5); // Try up to 5 times
                    if ($product) {
                        // Store in transient for 36 hours (36 * HOUR_IN_SECONDS)
                        set_transient($transient_key, $product, 36 * HOUR_IN_SECONDS);
                        $products[] = $product;
                    }
                    // If product is still false after retries, we skip it
                }
            }
        }
        
        wp_send_json($products);
        wp_die();
    }
    
    // New method to handle retries
    private function fetch_product_data_with_retry($asin, $max_attempts = 5) {
        $attempts = 0;
        $delay = 1; // Start with 1 second delay
        
        while ($attempts < $max_attempts) {
            $product = $this->fetch_product_data($asin);
            
            // If we got a valid product with a real title (not just the fallback)
            if ($product && $product['title'] !== 'Product ' . $asin) {
                return $product;
            }
            
            // Increment attempts and wait before trying again
            $attempts++;
            
            if ($attempts < $max_attempts) {
                // Exponential backoff: 1s, 2s, 4s, 8s...
                sleep($delay);
                $delay *= 2;
            }
        }
        
        // After all attempts, if we have a fallback product, return it
        // Otherwise return false to skip this product
        if (isset($product)) {
            return $product;
        }
        
        return false;
    }

    private function expand_and_extract_asin($short_url) {
        // First check if we have this URL in transient cache
        $url_transient_key = 'apg_url_' . md5($short_url);
        $cached_asin = get_transient($url_transient_key);
        
        if ($cached_asin !== false) {
            return $cached_asin;
        }
        
        // Clean the URL from any HTML markup
        $short_url = preg_replace('/">.*$/', '', $short_url);
        $short_url = html_entity_decode($short_url);
        
        // We need to follow the redirect to get the actual ASIN
        // Don't try to extract directly from the short URL as those are not ASINs
        
        // Set up curl with more options to better handle redirects
        $ch = curl_init($short_url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false); // Don't need headers in response
        curl_setopt($ch, CURLOPT_NOBODY, false); // Need the body
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10); // Allow up to 10 redirects
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Skip SSL verification
        
        $body = curl_exec($ch);
        $final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        
        // Pattern for Amazon product URLs with dp/ followed by ASIN
        if (preg_match('/\/dp\/([A-Z0-9]{10})(?:\/|\?|$)/i', $final_url, $matches)) {
            $asin = $matches[1];
            // Store in transient for 36 hours
            set_transient($url_transient_key, $asin, 36 * HOUR_IN_SECONDS);
            return $asin;
        }
        
        return false;
    }

    private function fetch_product_data($asin) {
        $this->options = get_option('apg_settings');
        $access_key = $this->options['access_key'] ?? '';
        $secret_key = $this->options['secret_key'] ?? '';
        $associate_tag = $this->options['associate_tag'] ?? '';
        
        // Get region settings with default
        $region_setting = isset($this->options['amazon_region']) && !empty($this->options['amazon_region']) 
            ? $this->options['amazon_region'] 
            : 'eu-west-1|it|www.amazon.it';
        
        list($region, $country_code, $marketplace) = explode('|', $region_setting);
        
        // If credentials are missing, return placeholder data
        if (empty($access_key) || empty($secret_key) || empty($associate_tag)) {
            return [
                'title' => 'Product ' . $asin,
                'image' => 'https://www.pngkey.com/png/detail/233-2332677_image-500580-placeholder-transparent.png',
                'url' => 'https://' . $marketplace . '/dp/' . $asin . '?tag=' . $associate_tag,
            ];
        }

        // Set up request parameters
        $service = 'ProductAdvertisingAPI';
        $host = 'webservices.amazon.' . $country_code;
        $uri = '/paapi5/getitems';
        $endpoint = "https://$host$uri";

        // Request parameters - ensure correct format
        $payload = json_encode([
            'ItemIds' => [$asin],
            'Resources' => [
                'Images.Primary.Medium',
                'ItemInfo.Title',
                'Offers.Listings.Price'
            ],
            'PartnerTag' => $associate_tag,
            'PartnerType' => 'Associates',
            'Marketplace' => $marketplace,
        ], JSON_UNESCAPED_SLASHES);

        // Request date and time - ensure correct timezone
        $amz_date = gmdate('Ymd\THis\Z');
        $date_stamp = gmdate('Ymd');

        // Prepare canonical request
        $method = 'POST';
        $canonical_uri = $uri;
        $canonical_querystring = '';
        
        $canonical_headers = "content-encoding:amz-1.0\n" .
                            "content-type:application/json; charset=utf-8\n" .
                            "host:$host\n" .
                            "x-amz-date:$amz_date\n" .
                            "x-amz-target:com.amazon.paapi5.v1.ProductAdvertisingAPIv1.GetItems\n";
        
        $signed_headers = "content-encoding;content-type;host;x-amz-date;x-amz-target";
        
        $payload_hash = hash('sha256', $payload);
        
        $canonical_request = "$method\n$canonical_uri\n$canonical_querystring\n$canonical_headers\n$signed_headers\n$payload_hash";
        
        // Prepare string to sign
        $algorithm = 'AWS4-HMAC-SHA256';
        $credential_scope = "$date_stamp/$region/$service/aws4_request";
        $string_to_sign = "$algorithm\n$amz_date\n$credential_scope\n" . hash('sha256', $canonical_request);
        
        // Calculate signature
        $signing_key = $this->getSignatureKey($secret_key, $date_stamp, $region, $service);
        $signature = hash_hmac('sha256', $string_to_sign, $signing_key);
        
        // Create authorization header
        $authorization = "$algorithm Credential=$access_key/$credential_scope, SignedHeaders=$signed_headers, Signature=$signature";
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        
        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'Content-Encoding: amz-1.0',
            'X-Amz-Date: ' . $amz_date,
            'X-Amz-Target: com.amazon.paapi5.v1.ProductAdvertisingAPIv1.GetItems',
            'Authorization: ' . $authorization,
            'User-Agent: Amazon Products Grid/1.0'
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Execute request
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        // Process response
        if ($status_code == 200) {
            $data = json_decode($response, true);
            
            if (isset($data['ItemsResult']['Items'][0])) {
                $item = $data['ItemsResult']['Items'][0];
                
                return [
                    'title' => (strlen($title = $item['ItemInfo']['Title']['DisplayValue'] ?? 'Product ' . $asin) > 50) ? substr($title, 0, 47) . '...' : $title,
                    'image' => $item['Images']['Primary']['Medium']['URL'] ?? 'https://www.pngkey.com/png/detail/233-2332677_image-500580-placeholder-transparent.png',
                    'url' => "https://www.amazon.it/dp/$asin?tag=$associate_tag",
                ];
            }
        }
        
        // Fallback to placeholder data if API request fails
        return [
            'title' => 'Product ' . $asin,
            'image' => 'https://www.pngkey.com/png/detail/233-2332677_image-500580-placeholder-transparent.png',
            'url' => 'https://www.amazon.it/dp/' . $asin . '?tag=' . $associate_tag,
        ];
    }
    
    // Helper function to generate the signing key
    private function getSignatureKey($key, $date, $region, $service) {
        $k_date = hash_hmac('sha256', $date, 'AWS4' . $key, true);
        $k_region = hash_hmac('sha256', $region, $k_date, true);
        $k_service = hash_hmac('sha256', $service, $k_region, true);
        $k_signing = hash_hmac('sha256', 'aws4_request', $k_service, true);
        return $k_signing;
    }

    public function add_plugin_page() {
        add_options_page('Amazon Products Grid', 'Amazon Products Grid', 'manage_options', 'amazon-products-grid', [$this, 'create_admin_page']);
    }

    public function create_admin_page() {
        $this->options = get_option('apg_settings');
        
        // Check if the clear transients button was clicked
        if (isset($_POST['clear_amazon_transients']) && check_admin_referer('clear_amazon_transients_nonce')) {
            $this->clear_all_transients();
            echo '<div class="notice notice-success is-dismissible"><p>All Amazon Products Grid transients have been cleared!</p></div>';
        }
        
        echo '<div class="wrap">';
        echo '<h1>Amazon Products Grid Configurations</h1>';
        
        // Add instructions section
        echo '<div style="margin-bottom: 30px; padding: 20px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 3px;">';
        echo '<h2 style="margin-top: 0;">How to Use This Plugin</h2>';
        echo '<p><strong>Basic Usage:</strong> Simply add the shortcode <code>[prodotti_amazon]</code> to any post or page where you want to display Amazon products.</p>';
        echo '<p>The plugin automatically detects Amazon product links (amzn.eu short links) in your content and displays them in a responsive grid.</p>';
        echo '<p><strong>Using with Elementor:</strong> You can also add this shortcode to your Elementor templates:</p>';
        echo '<ol>';
        echo '<li>Edit your template with Elementor</li>';
        echo '<li>Add a "Shortcode" widget to your layout</li>';
        echo '<li>Insert <code>[prodotti_amazon]</code> in the shortcode field</li>';
        echo '<li>Save and publish your template</li>';
        echo '</ol>';
        echo '<p><strong>Note:</strong> Make sure your post content includes Amazon product links (amzn.eu format) for the grid to display.</p>';
        echo '</div>';
        
        echo '<form method="post" action="options.php">';
        settings_fields('apg_option_group');
        do_settings_sections('amazon-products-grid-admin');
        submit_button();
        echo '</form>';
        
        // Add a separate form for the clear transients button
        echo '<div style="margin-top: 30px; padding: 20px; background: #f8f8f8; border-radius: 5px;">';
        echo '<h2>Cache Management</h2>';
        echo '<p>Click the button below to clear all cached Amazon product data. This can be useful if you want to refresh product information.</p>';
        echo '<form method="post">';
        wp_nonce_field('clear_amazon_transients_nonce');
        echo '<input type="submit" name="clear_amazon_transients" class="button button-secondary" value="Clear Amazon Products Cache" onclick="return confirm(\'Are you sure you want to clear all cached Amazon product data?\');">';
        echo '</form>';
        echo '</div>';
        
        echo '</div>'; // Close wrap
    }
    
    // Add a new method to clear all transients
    private function clear_all_transients() {
        global $wpdb;
        
        // Get all transients with our prefix
        $transients = $wpdb->get_results(
            "SELECT option_name FROM $wpdb->options 
            WHERE option_name LIKE '%_transient_apg_%' 
            OR option_name LIKE '%_transient_timeout_apg_%'"
        );
        
        $count = 0;
        foreach ($transients as $transient) {
            $name = str_replace('_transient_', '', $transient->option_name);
            $name = str_replace('_transient_timeout_', '', $name);
            
            if (delete_transient($name)) {
                $count++;
            }
        }
        
        return $count;
    }

    public function page_init() {
        register_setting('apg_option_group', 'apg_settings');

        add_settings_section('apg_api_section', 'Amazon API Configuration', null, 'amazon-products-grid-admin');
        add_settings_field('access_key', 'Access Key', [$this, 'field_callback'], 'amazon-products-grid-admin', 'apg_api_section', ['id' => 'access_key']);
        add_settings_field('secret_key', 'Secret Key', [$this, 'field_callback'], 'amazon-products-grid-admin', 'apg_api_section', ['id' => 'secret_key']);
        add_settings_field('associate_tag', 'Associate Tag', [$this, 'field_callback'], 'amazon-products-grid-admin', 'apg_api_section', ['id' => 'associate_tag']);
        add_settings_field('amazon_region', 'Amazon Region', [$this, 'region_callback'], 'amazon-products-grid-admin', 'apg_api_section');

        add_settings_section('apg_text_section', 'Custom Texts', null, 'amazon-products-grid-admin');
        add_settings_field('heading_text', 'Grid Title', [$this, 'field_callback'], 'amazon-products-grid-admin', 'apg_text_section', ['id' => 'heading_text']);
        add_settings_field('subheading_text', 'Grid Subtitle', [$this, 'field_callback'], 'amazon-products-grid-admin', 'apg_text_section', ['id' => 'subheading_text']);

        add_settings_section('apg_style_section', 'Grid Style', null, 'amazon-products-grid-admin');
        add_settings_field('columns_group', 'Column Number', [$this, 'columns_callback'], 'amazon-products-grid-admin', 'apg_style_section');
        add_settings_field('custom_css', 'Custom CSS', [$this, 'textarea_callback'], 'amazon-products-grid-admin', 'apg_style_section', ['id' => 'custom_css']);
    }

    public function field_callback($args) {
        $id = $args['id'];
        $value = $this->options[$id] ?? '';
        echo "<input type='text' id='$id' name='apg_settings[$id]' value='" . esc_attr($value) . "' style='width: 400px;'>";
    }

    public function textarea_callback($args) {
        $id = $args['id'];
        $value = $this->options[$id] ?? '';
        echo "<textarea id='$id' name='apg_settings[$id]' rows='10' cols='70'>$value</textarea>";
    }
    
    // Add the columns_callback method
    public function columns_callback() {
        $desktop_cols = isset($this->options['desktop_columns']) ? intval($this->options['desktop_columns']) : 5;
        $tablet_cols = isset($this->options['tablet_columns']) ? intval($this->options['tablet_columns']) : 3;
        $mobile_cols = isset($this->options['mobile_columns']) ? intval($this->options['mobile_columns']) : 2;
        
        echo '<div style="display: flex; gap: 10px; align-items: center;">';
        echo '<label>Desktop: <input type="number" min="1" max="6" id="desktop_columns" name="apg_settings[desktop_columns]" value="' . esc_attr($desktop_cols) . '" style="width: 60px;"></label>';
        echo '<label>Tablet: <input type="number" min="1" max="4" id="tablet_columns" name="apg_settings[tablet_columns]" value="' . esc_attr($tablet_cols) . '" style="width: 60px;"></label>';
        echo '<label>Mobile: <input type="number" min="1" max="2" id="mobile_columns" name="apg_settings[mobile_columns]" value="' . esc_attr($mobile_cols) . '" style="width: 60px;"></label>';
        echo '</div>';
    }
    
    // Add the region_callback method inside the class
    public function region_callback() {
        $regions = [
            'eu-west-1|it|www.amazon.it' => 'Italy (www.amazon.it)',
            'eu-west-1|fr|www.amazon.fr' => 'France (www.amazon.fr)',
            'eu-west-1|es|www.amazon.es' => 'Spain (www.amazon.es)',
            'eu-west-1|de|www.amazon.de' => 'Germany (www.amazon.de)',
            'eu-west-1|uk|www.amazon.co.uk' => 'United Kingdom (www.amazon.co.uk)',
            'us-east-1|us|www.amazon.com' => 'United States (www.amazon.com)',
            'us-west-2|ca|www.amazon.ca' => 'Canada (www.amazon.ca)',
            'ap-northeast-1|jp|www.amazon.co.jp' => 'Japan (www.amazon.co.jp)',
            'ap-southeast-2|au|www.amazon.com.au' => 'Australia (www.amazon.com.au)',
            'eu-west-1|nl|www.amazon.nl' => 'Netherlands (www.amazon.nl)',
            'eu-west-1|se|www.amazon.se' => 'Sweden (www.amazon.se)',
            'eu-west-1|pl|www.amazon.pl' => 'Poland (www.amazon.pl)',
        ];
        
        $selected = $this->options['amazon_region'] ?? 'eu-west-1|it|www.amazon.it'; // Default to Italy
        
        echo '<select id="amazon_region" name="apg_settings[amazon_region]" style="width: 400px;">';
        foreach ($regions as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($selected, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }
}

new Amazon_Products_Grid();