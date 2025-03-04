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
        
        // Find all main category links - based on observed Marktplaats structure
        // Look for links with a pattern like /cp/NUMBER/CATEGORY-NAME/
        $category_nodes = $xpath->query('//a[contains(@href, "/cp/")]');
        
        if ($category_nodes && $category_nodes->length > 0) {
            foreach ($category_nodes as $node) {
                $href = $node->getAttribute('href');
                
                // Extract category ID and name from URL pattern like /cp/91/auto-kopen/
                if (preg_match('/\/cp\/(\d+)\/([^\/]+)\//', $href, $matches)) {
                    $id = $matches[1];
                    $name = trim($node->textContent);
                    
                    // Add to categories if not already exists
                    if (!empty($id) && !empty($name) && !isset($categories[$id])) {
                        $categories[$id] = $name;
                    }
                }
            }
        }
        
        // If the first method doesn't find categories, try an alternative method
        if (empty($categories)) {
            // Look for links that go to category pages
            $category_nodes = $xpath->query('//a[contains(@href, "/l/") and not(contains(@href, "?"))]');
            
            if ($category_nodes && $category_nodes->length > 0) {
                foreach ($category_nodes as $node) {
                    $href = $node->getAttribute('href');
                    
                    // Check if this is a main category link (has pattern /l/category-name/)
                    if (preg_match('/\/l\/([^\/]+)\/$/', $href, $matches)) {
                        $slug = $matches[1];
                        $name = trim($node->textContent);
                        
                        // Use slug as ID if no numeric ID is available
                        $id = $slug;
                        
                        // Add to categories if not already exists and not a subcategory
                        if (!empty($id) && !empty($name) && !isset($categories[$id]) && 
                            // Exclude subcategories which would have more slashes
                            substr_count($href, '/') <= 4) {
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
        // Get all categories first
        $categories = get_transient('wc_marktplaats_categories');
        
        if (false === $categories) {
            $categories = $this->fetch_categories();
        }
        
        // Check if the category exists
        if (!isset($categories[$category_id])) {
            $this->log_error("Category ID $category_id not found in categories list");
            return array();
        }
        
        // Get category name
        $category_name = $categories[$category_id];
        
        // Determine the URL for this category based on the ID format
        if (is_numeric($category_id)) {
            // Numeric ID format
            $url = "https://www.marktplaats.nl/cp/{$category_id}/" . $this->create_slug($category_name) . "/";
        } else {
            // Slug format
            $url = "https://www.marktplaats.nl/l/{$category_id}/";
        }
        
        // Fetch category page
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'user-agent' => $this->user_agent,
        ));
        
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            // Try alternative URL format if first attempt failed
            if (is_numeric($category_id)) {
                $alt_url = "https://www.marktplaats.nl/l/" . $this->create_slug($category_name) . "/";
                $response = wp_remote_get($alt_url, array(
                    'timeout' => 15,
                    'user-agent' => $this->user_agent,
                ));
            }
            
            if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
                $this->log_error('Failed to fetch subcategories for ' . $category_id . ': ' . 
                    (is_wp_error($response) ? $response->get_error_message() : 'Status code: ' . wp_remote_retrieve_response_code($response)));
                return array();
            }
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
        
        // Find all subcategory links
        // Based on observed Marktplaats structure, subcategory links are inside the category page
        // with a URL pattern like /l/main-category/subcategory/
        $subcategory_nodes = $xpath->query('//a[contains(@href, "/l/' . (is_numeric($category_id) ? $this->create_slug($category_name) : $category_id) . '/")]');
        
        if ($subcategory_nodes && $subcategory_nodes->length > 0) {
            foreach ($subcategory_nodes as $node) {
                $href = $node->getAttribute('href');
                
                // Extract subcategory from URL pattern like /l/main-category/subcategory/
                if (preg_match('/\/l\/[^\/]+\/([^\/]+)\//', $href, $matches)) {
                    $slug = $matches[1];
                    $name = trim($node->textContent);
                    
                    // Use slug as ID
                    $id = $slug;
                    
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
        $string = str_replace(array(' ', '\'', '|'), array('-', '', '-'), $string);
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