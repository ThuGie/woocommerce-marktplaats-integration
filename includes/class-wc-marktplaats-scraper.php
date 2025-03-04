<?php
/**
 * Web scraper class for Marktplaats categories
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Marktplaats_Scraper {

    /**
     * User agent for requests
     */
    private $user_agent;

    /**
     * Constructor
     */
    public function __construct() {
        $this->user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
    }

    /**
     * Fetch categories from Marktplaats.nl
     */
    public function fetch_categories() {
        // Use WordPress HTTP API to fetch the Marktplaats homepage
        $response = wp_remote_get('https://www.marktplaats.nl/', array(
            'timeout' => 15,
            'user-agent' => $this->user_agent,
        ));
        
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            $this->log_error('Failed to fetch Marktplaats homepage: ' . 
                (is_wp_error($response) ? $response->get_error_message() : 'Status code: ' . wp_remote_retrieve_response_code($response)));
            return array();
        }
        
        $html = wp_remote_retrieve_body($response);
        
        // Parse the HTML to extract categories
        $categories = array();
        
        // Create a new DOMDocument
        $dom = new DOMDocument();
        
        // Suppress warnings about malformed HTML
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        
        // Create a new DOMXPath
        $xpath = new DOMXPath($dom);
        
        // Find all category links - this XPath expression may need adjustment based on Marktplaats.nl structure
        $category_nodes = $xpath->query('//a[contains(@class, "category-link") or contains(@href, "/c/")]');
        
        if ($category_nodes && $category_nodes->length > 0) {
            foreach ($category_nodes as $node) {
                $href = $node->getAttribute('href');
                
                // Only process if it looks like a category link
                if (strpos($href, '/c/') !== false) {
                    // Extract category ID
                    if (preg_match('/\/c\/([^\/]+)\/(\d+)/', $href, $matches)) {
                        $id = $matches[2];
                        $name = trim($node->textContent);
                        
                        // Add to categories if not already exists
                        if (!empty($id) && !empty($name) && !isset($categories[$id])) {
                            $categories[$id] = $name;
                        }
                    }
                }
            }
        }
        
        // Log results
        $this->log_info('Fetched ' . count($categories) . ' categories from Marktplaats.nl');
        
        return $categories;
    }
    
    /**
     * Fetch subcategories for a specific category
     */
    public function fetch_subcategories($category_id) {
        // Get all categories first to find the correct URL
        $categories = get_transient('wc_marktplaats_categories');
        
        if (false === $categories) {
            $categories = $this->fetch_categories();
        }
        
        // Check if the category exists
        if (!isset($categories[$category_id])) {
            $this->log_error("Category ID $category_id not found in categories list");
            return array();
        }
        
        // Create slug from category name
        $category_name = $categories[$category_id];
        $slug = $this->create_slug($category_name);
        
        // Build URL
        $url = "https://www.marktplaats.nl/c/$slug/$category_id/";
        
        // Fetch category page
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'user-agent' => $this->user_agent,
        ));
        
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            $this->log_error('Failed to fetch subcategories for ' . $category_id . ': ' . 
                (is_wp_error($response) ? $response->get_error_message() : 'Status code: ' . wp_remote_retrieve_response_code($response)));
            return array();
        }
        
        $html = wp_remote_retrieve_body($response);
        
        // Parse HTML to extract subcategories
        $subcategories = array();
        
        // Create a new DOMDocument
        $dom = new DOMDocument();
        
        // Suppress warnings about malformed HTML
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        
        // Create a new DOMXPath
        $xpath = new DOMXPath($dom);
        
        // Find all subcategory links - this XPath expression may need adjustment based on Marktplaats.nl structure
        $subcategory_nodes = $xpath->query('//a[contains(@href, "/c/' . $slug . '/' . $category_id . '/") and not(contains(@href, "?"))]');
        
        if ($subcategory_nodes && $subcategory_nodes->length > 0) {
            foreach ($subcategory_nodes as $node) {
                $href = $node->getAttribute('href');
                
                // Extract subcategory ID
                if (preg_match('/\/c\/[^\/]+\/\d+\/([^\/]+)\/(\d+)/', $href, $matches)) {
                    $id = $matches[2];
                    $name = trim($node->textContent);
                    
                    // Add to subcategories if not already exists
                    if (!empty($id) && !empty($name) && !isset($subcategories[$id])) {
                        $subcategories[$id] = $name;
                    }
                }
            }
        }
        
        // Log results
        $this->log_info('Fetched ' . count($subcategories) . ' subcategories for category ' . $category_id);
        
        return $subcategories;
    }
    
    /**
     * Create a URL-friendly slug from a string
     */
    private function create_slug($string) {
        $string = strtolower($string);
        $string = str_replace(array(' ', '\''), array('-', ''), $string);
        $string = preg_replace('/[^a-z0-9\-]/', '', $string);
        return $string;
    }
    
    /**
     * Log error message
     */
    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WC Marktplaats] ERROR: ' . $message);
        }
    }
    
    /**
     * Log info message
     */
    private function log_info($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WC Marktplaats] INFO: ' . $message);
        }
    }
}