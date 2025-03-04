<?php
/**
 * Category manager class for WooCommerce to Marktplaats
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Marktplaats_Category_Manager {

    /**
     * Scraper instance
     */
    private $scraper;

    /**
     * Constructor
     */
    public function __construct() {
        $this->scraper = new WC_Marktplaats_Scraper();
    }

    /**
     * Get Marktplaats categories from cache or fetch them from the website
     */
    public function get_marktplaats_categories() {
        // Try to get categories from cache
        $cached_categories = get_transient('wc_marktplaats_categories');
        
        if (false !== $cached_categories) {
            return $cached_categories;
        }
        
        // If not in cache, fetch them from Marktplaats
        $categories = $this->fetch_marktplaats_categories();
        
        // Get cache duration
        $cache_duration = intval(get_option('wc_marktplaats_category_cache_duration', 7));
        
        // Cache for specified days
        set_transient('wc_marktplaats_categories', $categories, $cache_duration * DAY_IN_SECONDS);
        
        return $categories;
    }
    
    /**
     * Fetch categories from Marktplaats.nl website
     */
    public function fetch_marktplaats_categories() {
        // Use the scraper to fetch categories
        $categories = $this->scraper->fetch_categories();
        
        // If we couldn't parse any categories, fall back to defaults
        if (empty($categories)) {
            return $this->get_fallback_categories();
        }
        
        return $categories;
    }
    
    /**
     * Get subcategories for a specific category
     */
    public function get_marktplaats_subcategories($category_id) {
        // Try to get from cache first
        $cache_key = 'wc_marktplaats_subcategories_' . $category_id;
        $cached_subcategories = get_transient($cache_key);
        
        if (false !== $cached_subcategories) {
            return $cached_subcategories;
        }
        
        // If not in cache, fetch from Marktplaats
        $subcategories = $this->fetch_marktplaats_subcategories($category_id);
        
        // Get cache duration
        $cache_duration = intval(get_option('wc_marktplaats_category_cache_duration', 7));
        
        // Cache for specified days
        set_transient($cache_key, $subcategories, $cache_duration * DAY_IN_SECONDS);
        
        return $subcategories;
    }
    
    /**
     * Fetch subcategories from Marktplaats for a specific category
     */
    public function fetch_marktplaats_subcategories($category_id) {
        // Use the scraper to fetch subcategories
        $subcategories = $this->scraper->fetch_subcategories($category_id);
        
        return $subcategories;
    }
    
    /**
     * Provide fallback categories in case fetch fails
     */
    public function get_fallback_categories() {
        return array(
            '1' => 'Auto\'s',
            '2' => 'Motoren',
            '3' => 'Elektronica',
            '4' => 'Computers en Software',
            '5' => 'Kleding | Dames',
            '6' => 'Kleding | Heren',
            '7' => 'Meubels',
            '8' => 'Huis en Inrichting',
            '9' => 'Antiek en Kunst',
            '10' => 'Audio, Tv en Foto',
            '11' => 'Boeken',
            '12' => 'Muziek en Instrumenten',
            '13' => 'Verzamelen',
            '14' => 'Sport en Fitness',
            '15' => 'Spelcomputers en Games',
            '16' => 'Hobby en Vrije tijd',
        );
    }
    
    /**
     * AJAX handler for refreshing categories
     */
    public function ajax_refresh_categories() {
        check_ajax_referer('wc_marktplaats_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permission denied');
        }
        
        // Delete the transient to force a refresh
        delete_transient('wc_marktplaats_categories');
        
        // Fetch fresh categories
        $categories = $this->fetch_marktplaats_categories();
        
        // Store in cache with user-defined duration
        $cache_duration = intval(get_option('wc_marktplaats_category_cache_duration', 7));
        set_transient('wc_marktplaats_categories', $categories, $cache_duration * DAY_IN_SECONDS);
        
        // Update last update time
        update_option('wc_marktplaats_last_category_update', time());
        
        wp_send_json_success(array(
            'count' => count($categories),
            'categories' => $categories
        ));
    }
    
    /**
     * AJAX handler for getting subcategories
     */
    public function ajax_get_subcategories() {
        check_ajax_referer('wc_marktplaats_nonce', 'nonce');
        
        if (!isset($_POST['category_id'])) {
            wp_send_json_error('Missing category ID');
        }
        
        $category_id = sanitize_text_field($_POST['category_id']);
        $subcategories = $this->get_marktplaats_subcategories($category_id);
        
        wp_send_json_success(array('subcategories' => $subcategories));
    }
    
    /**
     * Get category and subcategory for a product
     */
    public function get_product_category($product_id) {
        // Check for category override
        $category_override = get_post_meta($product_id, '_marktplaats_category_override', true);
        $subcategory_override = get_post_meta($product_id, '_marktplaats_subcategory_override', true);
        
        if (!empty($category_override)) {
            return array(
                'category' => $category_override,
                'subcategory' => $subcategory_override
            );
        }
        
        // No override, get from product categories
        $product = wc_get_product($product_id);
        $product_cats = $product->get_category_ids();
        
        if (empty($product_cats)) {
            return array('category' => '', 'subcategory' => '');
        }
        
        // Get mapping
        $category_mapping = get_option('wc_marktplaats_category_mapping', array());
        
        // Check each product category
        foreach ($product_cats as $cat_id) {
            if (isset($category_mapping[$cat_id]) && !empty($category_mapping[$cat_id]['cat'])) {
                return array(
                    'category' => $category_mapping[$cat_id]['cat'],
                    'subcategory' => isset($category_mapping[$cat_id]['subcat']) ? $category_mapping[$cat_id]['subcat'] : ''
                );
            }
        }
        
        // No mapping found
        return array('category' => '', 'subcategory' => '');
    }
}