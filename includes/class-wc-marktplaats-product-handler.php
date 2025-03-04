<?php
/**
 * Product handler class for WooCommerce to Marktplaats
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Marktplaats_Product_Handler {

    /**
     * Category manager instance
     */
    private $category_manager;

    /**
     * Constructor
     */
    public function __construct() {
        $this->category_manager = new WC_Marktplaats_Category_Manager();
    }

    /**
     * Post product to Marktplaats
     */
    public function post_product_to_marktplaats($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return new WP_Error('invalid_product', __('Invalid product', 'wc-marktplaats'));
        }
        
        // Get Marktplaats credentials
        $username = get_option('wc_marktplaats_username');
        $password = get_option('wc_marktplaats_password');
        
        if (empty($username) || empty($password)) {
            return new WP_Error('missing_credentials', __('Marktplaats credentials not set. Please configure them in the settings page.', 'wc-marktplaats'));
        }
        
        // Get category information
        $category_info = $this->category_manager->get_product_category($product_id);
        
        if (empty($category_info['category'])) {
            return new WP_Error('missing_category', __('No Marktplaats category mapping found for this product. Please set up category mapping in the settings.', 'wc-marktplaats'));
        }
        
        // Prepare product data
        $product_data = $this->prepare_product_data($product);
        
        // For a real implementation, this is where you would perform the actual posting
        // using a browser automation tool like PHP-WebDriver
        
        // For this example, we'll simulate a successful post
        $result = $this->simulate_marktplaats_post($product_id, $product_data, $category_info);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Update product meta with Marktplaats info
        update_post_meta($product_id, '_marktplaats_id', $result['id']);
        update_post_meta($product_id, '_marktplaats_url', $result['url']);
        update_post_meta($product_id, '_marktplaats_posted_date', current_time('mysql'));
        
        return true;
    }
    
    /**
     * Update product on Marktplaats
     */
    public function update_product_on_marktplaats($product_id) {
        $marktplaats_id = get_post_meta($product_id, '_marktplaats_id', true);
        
        if (!$marktplaats_id) {
            return new WP_Error('not_posted', __('This product has not been posted to Marktplaats yet.', 'wc-marktplaats'));
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            return new WP_Error('invalid_product', __('Invalid product', 'wc-marktplaats'));
        }
        
        // Get Marktplaats credentials
        $username = get_option('wc_marktplaats_username');
        $password = get_option('wc_marktplaats_password');
        
        if (empty($username) || empty($password)) {
            return new WP_Error('missing_credentials', __('Marktplaats credentials not set. Please configure them in the settings page.', 'wc-marktplaats'));
        }
        
        // Prepare product data
        $product_data = $this->prepare_product_data($product);
        
        // For a real implementation, this is where you would perform the actual update
        // using a browser automation tool
        
        // For this example, we'll simulate a successful update
        update_post_meta($product_id, '_marktplaats_updated_date', current_time('mysql'));
        
        return true;
    }
    
    /**
     * Prepare product data for Marktplaats
     */
    private function prepare_product_data($product) {
        // Get prefix and suffix for description
        $description_prefix = get_option('wc_marktplaats_default_description_prefix', '');
        $description_suffix = get_option('wc_marktplaats_default_description_suffix', '');
        
        // Basic product data
        $data = array(
            'title' => $product->get_name(),
            'description' => $description_prefix . "\n\n" . 
                             $product->get_description() . "\n\n" . 
                             $description_suffix,
            'price' => $product->get_price(),
            'shipping' => get_option('wc_marktplaats_default_shipping', 'both'),
            'images' => array(),
        );
        
        // Get product images
        $image_ids = array();
        
        // Main image
        if ($product->get_image_id()) {
            $image_ids[] = $product->get_image_id();
        }
        
        // Gallery images
        if ($product->get_gallery_image_ids()) {
            $image_ids = array_merge($image_ids, $product->get_gallery_image_ids());
        }
        
        // Get image URLs
        foreach ($image_ids as $image_id) {
            $image_url = wp_get_attachment_url($image_id);
            if ($image_url) {
                $data['images'][] = $image_url;
            }
        }
        
        return $data;
    }
    
    /**
     * Simulate posting to Marktplaats
     * 
     * In a real implementation, this would be replaced with actual browser automation
     */
    private function simulate_marktplaats_post($product_id, $product_data, $category_info) {
        // In a real implementation, this is where you would:
        // 1. Launch a headless browser
        // 2. Log in to Marktplaats
        // 3. Navigate to the "Place ad" page
        // 4. Fill in the product details
        // 5. Select categories
        // 6. Upload images
        // 7. Submit the form
        // 8. Extract the listing URL and ID
        
        // For demonstration purposes, we'll generate a fake listing ID and URL
        $listing_id = 'MP' . time() . rand(1000, 9999);
        $listing_url = 'https://www.marktplaats.nl/v/listing/' . $listing_id;
        
        return array(
            'id' => $listing_id,
            'url' => $listing_url
        );
    }
    
    /**
     * Implementation guide for browser automation (pseudo-code)
     * 
     * This method outlines how the actual browser automation would be implemented
     * using a library like PHP-WebDriver with Selenium
     */
    private function browser_automation_guide() {
        /*
        // This is pseudo-code to illustrate the process
        
        // 1. Set up WebDriver
        $capabilities = DesiredCapabilities::chrome();
        $options = new ChromeOptions();
        $options->addArguments(['--headless', '--disable-gpu', '--window-size=1920,1080']);
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        $driver = RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities);
        
        try {
            // 2. Navigate to Marktplaats login page
            $driver->get('https://www.marktplaats.nl/account/login.html');
            
            // 3. Log in
            $driver->findElement(WebDriverBy::id('email'))->sendKeys($this->username);
            $driver->findElement(WebDriverBy::id('password'))->sendKeys($this->password);
            $driver->findElement(WebDriverBy::id('login-button'))->click();
            $driver->wait()->until(WebDriverExpectedCondition::urlContains('dashboard'));
            
            // 4. Navigate to place ad page
            $driver->get('https://www.marktplaats.nl/plaats');
            
            // 5. Fill in form fields
            $driver->findElement(WebDriverBy::id('title'))->sendKeys($this->product_title);
            $driver->findElement(WebDriverBy::id('description'))->sendKeys($this->product_description);
            $driver->findElement(WebDriverBy::id('price'))->sendKeys($this->product_price);
            
            // 6. Select category
            $driver->findElement(WebDriverBy::className('category-selector'))->click();
            $driver->findElement(WebDriverBy::xpath("//a[contains(@data-category-id, '{$this->category_id}')]"))->click();
            
            // 7. Select subcategory if needed
            if (!empty($this->subcategory_id)) {
                $driver->findElement(WebDriverBy::xpath("//a[contains(@data-subcategory-id, '{$this->subcategory_id}')]"))->click();
            }
            
            // 8. Upload images
            foreach ($this->product_images as $image_path) {
                $driver->findElement(WebDriverBy::id('image-upload'))->sendKeys($image_path);
                // Wait for upload to complete
                $driver->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(
                    WebDriverBy::className('upload-complete')
                ));
            }
            
            // 9. Submit form
            $driver->findElement(WebDriverBy::id('submit-button'))->click();
            
            // 10. Wait for completion and get the listing URL
            $driver->wait()->until(WebDriverExpectedCondition::urlContains('/v/'));
            $listing_url = $driver->getCurrentURL();
            
            // 11. Extract the listing ID from the URL
            if (preg_match('/\/v\/[^\/]+\/(\d+)/', $listing_url, $matches)) {
                $listing_id = $matches[1];
            }
            
            return array(
                'id' => $listing_id,
                'url' => $listing_url
            );
        } finally {
            // Always close the browser
            $driver->quit();
        }
        */
    }
}