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
        
        // Process description - remove HTML tags
        $description = $product->get_description();
        if (empty($description)) {
            $description = $product->get_short_description();
        }
        $description = strip_tags($description);
        
        // Basic product data
        $data = array(
            'title' => $product->get_name(),
            'description' => $description_prefix . "\n\n" . 
                             $description . "\n\n" . 
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
     * Implementation guide for browser automation for Marktplaats.nl
     * 
     * This method provides detailed steps for implementing web automation
     * using a library like PHP-WebDriver with Selenium
     */
    private function browser_automation_guide() {
        /*
        // This is a detailed guide for implementing browser automation with Marktplaats.nl
        
        // 1. Set up WebDriver
        $capabilities = DesiredCapabilities::chrome();
        $options = new ChromeOptions();
        $options->addArguments(['--headless', '--disable-gpu', '--window-size=1920,1080']);
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        $driver = RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities);
        
        try {
            // 2. Navigate to Marktplaats login page
            $driver->get('https://www.marktplaats.nl/identity/v2/login');
            
            // 3. Accept cookies if dialog appears
            try {
                $cookieAcceptButton = $driver->findElement(WebDriverBy::xpath("//button[contains(text(), 'Accepteren')]"));
                if ($cookieAcceptButton) {
                    $cookieAcceptButton->click();
                    // Wait for dialog to disappear
                    $driver->wait(10)->until(WebDriverExpectedCondition::invisibilityOfElementLocated(
                        WebDriverBy::xpath("//button[contains(text(), 'Accepteren')]")
                    ));
                }
            } catch (Exception $e) {
                // Cookie dialog might not appear, so continue
            }
            
            // 4. Log in
            $driver->findElement(WebDriverBy::id('email'))->sendKeys($this->username);
            $driver->findElement(WebDriverBy::id('password'))->sendKeys($this->password);
            $driver->findElement(WebDriverBy::xpath("//button[@type='submit']"))->click();
            
            // Wait for login to complete
            $driver->wait()->until(WebDriverExpectedCondition::urlContains('account'));
            
            // 5. Navigate to place ad page
            $driver->get('https://link.marktplaats.nl/link/placead/start');
            
            // 6. Select category
            // Wait for category selection page to load
            $driver->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::xpath("//h1[contains(text(), 'Kies een categorie')]")
            ));
            
            // Find and click the main category
            $mainCategoryElement = $driver->findElement(
                WebDriverBy::xpath("//a[contains(@href, '/cp/" . $this->category_id . "/')]")
            );
            $mainCategoryElement->click();
            
            // Wait for subcategory selection to appear if needed
            if (!empty($this->subcategory_id)) {
                $driver->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
                    WebDriverBy::xpath("//a[contains(@href, '" . $this->subcategory_id . "')]")
                ));
                
                $subcategoryElement = $driver->findElement(
                    WebDriverBy::xpath("//a[contains(@href, '" . $this->subcategory_id . "')]")
                );
                $subcategoryElement->click();
            }
            
            // Wait for the ad form to load
            $driver->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::name("title")
            ));
            
            // 7. Fill in form fields
            // Title
            $driver->findElement(WebDriverBy::name("title"))->sendKeys($this->product_title);
            
            // Description
            $driver->findElement(WebDriverBy::name("description"))->sendKeys($this->product_description);
            
            // Price
            $driver->findElement(WebDriverBy::name("priceValue"))->sendKeys($this->product_price);
            
            // Shipping options
            switch ($this->shipping_option) {
                case 'pickup':
                    $driver->findElement(WebDriverBy::xpath("//input[@value='PICKUP_ONLY']"))->click();
                    break;
                case 'shipping':
                    $driver->findElement(WebDriverBy::xpath("//input[@value='SHIPPING_ONLY']"))->click();
                    break;
                case 'both':
                default:
                    $driver->findElement(WebDriverBy::xpath("//input[@value='PICKUP_AND_SHIPPING']"))->click();
                    break;
            }
            
            // 8. Upload images
            foreach ($this->product_images as $index => $image_path) {
                // Convert URL to local file
                $temp_file = $this->download_image_to_temp($image_path);
                
                if ($index === 0) {
                    // First image might have a different selector
                    $driver->findElement(WebDriverBy::xpath("//input[@type='file']"))->sendKeys($temp_file);
                } else {
                    // Additional images might need to click an "Add image" button first
                    try {
                        $driver->findElement(WebDriverBy::xpath("//button[contains(text(), 'Voeg foto toe')]"))->click();
                        $driver->findElement(WebDriverBy::xpath("//input[@type='file']"))->sendKeys($temp_file);
                    } catch (Exception $e) {
                        // If the button is not found, try the direct approach
                        $driver->findElement(WebDriverBy::xpath("//input[@type='file']"))->sendKeys($temp_file);
                    }
                }
                
                // Wait for image to upload
                $driver->wait(30)->until(WebDriverExpectedCondition::presenceOfElementLocated(
                    WebDriverBy::xpath("//div[contains(@class, 'thumbnail')]")
                ));
                
                // Clean up temp file
                if (file_exists($temp_file)) {
                    unlink($temp_file);
                }
            }
            
            // 9. Submit form
            $driver->findElement(WebDriverBy::xpath("//button[@type='submit']"))->click();
            
            // 10. Wait for completion and get the listing URL
            $driver->wait(60)->until(WebDriverExpectedCondition::urlContains('/v/'));
            $listing_url = $driver->getCurrentURL();
            
            // 11. Extract the listing ID from the URL
            $listing_id = '';
            if (preg_match('/\/v\/[^\/]+\/([^\/]+)/', $listing_url, $matches)) {
                $listing_id = $matches[1];
            } else {
                // Alternative ID extraction as fallback
                $listing_id = 'MP' . time();
            }
            
            return array(
                'id' => $listing_id,
                'url' => $listing_url
            );
        } catch (Exception $e) {
            return new WP_Error('automation_error', $e->getMessage());
        } finally {
            // Always close the browser
            $driver->quit();
        }
        */
    }
    
    /**
     * Download image from URL to temporary file
     * 
     * This is needed because WebDriver requires local file paths for file uploads
     */
    private function download_image_to_temp($image_url) {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/wc-marktplaats/temp';
        
        // Create temp directory if it doesn't exist
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        // Generate a unique filename
        $filename = 'temp-' . md5($image_url . time()) . '.jpg';
        $temp_file = $temp_dir . '/' . $filename;
        
        // Download image
        $image_data = file_get_contents($image_url);
        if ($image_data) {
            file_put_contents($temp_file, $image_data);
            return $temp_file;
        }
        
        return '';
    }
    
    /**
     * Create new implementation of the real browser automation
     * 
     * This method would be implemented with the actual automation logic
     * based on the guide above. It would be called from post_product_to_marktplaats
     * instead of simulate_marktplaats_post.
     */
    private function real_marktplaats_post($product_id, $product_data, $category_info) {
        // To implement the actual browser automation:
        // 1. Install PHP-WebDriver and set up Selenium WebDriver
        // 2. Copy the code from browser_automation_guide() and adapt it
        // 3. Replace simulate_marktplaats_post() calls with this method
        
        // For now, fall back to simulation
        return $this->simulate_marktplaats_post($product_id, $product_data, $category_info);
    }
}
