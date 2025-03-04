<?php
/**
 * Plugin Name: WooCommerce to Marktplaats Web Automation
 * Description: Post WooCommerce products to Marktplaats.nl through web automation
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: wc-marktplaats
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 7.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('WC_MARKTPLAATS_VERSION', '1.0.0');
define('WC_MARKTPLAATS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_MARKTPLAATS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Check if WooCommerce is active
 */
function wc_marktplaats_check_woocommerce() {
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        add_action('admin_notices', 'wc_marktplaats_missing_wc_notice');
        return false;
    }
    return true;
}

/**
 * Display WooCommerce missing notice
 */
function wc_marktplaats_missing_wc_notice() {
    ?>
    <div class="error">
        <p><?php _e('WooCommerce to Marktplaats integration requires WooCommerce to be installed and active.', 'wc-marktplaats'); ?></p>
    </div>
    <?php
}

/**
 * Initialize the plugin
 */
function wc_marktplaats_init() {
    if (wc_marktplaats_check_woocommerce()) {
        // Include required files
        require_once WC_MARKTPLAATS_PLUGIN_DIR . 'includes/class-wc-marktplaats-integration.php';
        require_once WC_MARKTPLAATS_PLUGIN_DIR . 'includes/class-wc-marktplaats-category-manager.php';
        require_once WC_MARKTPLAATS_PLUGIN_DIR . 'includes/class-wc-marktplaats-scraper.php';
        require_once WC_MARKTPLAATS_PLUGIN_DIR . 'includes/class-wc-marktplaats-product-handler.php';
        
        // Initialize the main class
        new WC_Marktplaats_Integration();
    }
}
add_action('plugins_loaded', 'wc_marktplaats_init');

/**
 * Register activation and deactivation hooks
 */
register_activation_hook(__FILE__, 'wc_marktplaats_activation');
register_deactivation_hook(__FILE__, 'wc_marktplaats_deactivation');

/**
 * Activation function
 */
function wc_marktplaats_activation() {
    // Set default options
    add_option('wc_marktplaats_category_cache_duration', 7);
    
    // Create necessary directories
    $upload_dir = wp_upload_dir();
    $marktplaats_dir = $upload_dir['basedir'] . '/wc-marktplaats';
    
    if (!file_exists($marktplaats_dir)) {
        wp_mkdir_p($marktplaats_dir);
    }
    
    // Create an index.php file to prevent directory listing
    if (!file_exists($marktplaats_dir . '/index.php')) {
        $index_file = fopen($marktplaats_dir . '/index.php', 'w');
        fwrite($index_file, '<?php // Silence is golden');
        fclose($index_file);
    }
    
    // Schedule a daily event to refresh categories if needed
    if (!wp_next_scheduled('wc_marktplaats_refresh_categories')) {
        wp_schedule_event(time(), 'daily', 'wc_marktplaats_refresh_categories');
    }
}

/**
 * Deactivation function
 */
function wc_marktplaats_deactivation() {
    // Clear scheduled events
    wp_clear_scheduled_hook('wc_marktplaats_refresh_categories');
    
    // Remove transients
    delete_transient('wc_marktplaats_categories');
    
    // Get all transients with subcategories
    global $wpdb;
    $transients = $wpdb->get_col(
        "SELECT option_name FROM {$wpdb->options} 
        WHERE option_name LIKE '_transient_wc_marktplaats_subcategories_%'"
    );
    
    // Delete each subcategory transient
    foreach ($transients as $transient) {
        $transient_name = str_replace('_transient_', '', $transient);
        delete_transient($transient_name);
    }
}

/**
 * Add scheduled action to refresh categories
 */
add_action('wc_marktplaats_refresh_categories', 'wc_marktplaats_do_refresh_categories');

/**
 * Function to refresh categories based on cache duration
 */
function wc_marktplaats_do_refresh_categories() {
    require_once WC_MARKTPLAATS_PLUGIN_DIR . 'includes/class-wc-marktplaats-category-manager.php';
    $category_manager = new WC_Marktplaats_Category_Manager();
    
    // Get last update time
    $last_update = get_option('wc_marktplaats_last_category_update', 0);
    $cache_duration = intval(get_option('wc_marktplaats_category_cache_duration', 7));
    
    // If it's time to refresh
    if ((time() - $last_update) > ($cache_duration * DAY_IN_SECONDS)) {
        delete_transient('wc_marktplaats_categories');
        $category_manager->fetch_marktplaats_categories();
        update_option('wc_marktplaats_last_category_update', time());
    }
}