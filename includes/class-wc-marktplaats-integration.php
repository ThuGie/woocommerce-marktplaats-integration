<?php
/**
 * Main integration class for WooCommerce to Marktplaats
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Marktplaats_Integration {

    /**
     * Category manager instance
     */
    private $category_manager;
    
    /**
     * Product handler instance
     */
    private $product_handler;

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize dependencies
        $this->category_manager = new WC_Marktplaats_Category_Manager();
        $this->product_handler = new WC_Marktplaats_Product_Handler();
        
        // Add settings page
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add meta box to product edit page
        add_action('add_meta_boxes', array($this, 'add_product_metabox'));
        
        // Save metabox data
        add_action('save_post_product', array($this, 'save_metabox_data'));
        
        // Add action button to products list
        add_filter('post_row_actions', array($this, 'add_post_row_action'), 10, 2);
        
        // Process the post to Marktplaats action
        add_action('admin_init', array($this, 'process_post_to_marktplaats'));
        
        // Add bulk action
        add_filter('bulk_actions-edit-product', array($this, 'register_bulk_actions'));
        add_filter('handle_bulk_actions-edit-product', array($this, 'handle_bulk_actions'), 10, 3);
        
        // Add admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add AJAX handlers for category refresh and subcategory fetch
        add_action('wp_ajax_refresh_marktplaats_categories', array($this->category_manager, 'ajax_refresh_categories'));
        add_action('wp_ajax_get_marktplaats_subcategories', array($this->category_manager, 'ajax_get_subcategories'));
    }

    /**
     * Add menu item
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Marktplaats Integration', 'wc-marktplaats'),
            __('Marktplaats', 'wc-marktplaats'),
            'manage_woocommerce',
            'wc-marktplaats',
            array($this, 'settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('wc_marktplaats_settings', 'wc_marktplaats_username');
        register_setting('wc_marktplaats_settings', 'wc_marktplaats_password');
        register_setting('wc_marktplaats_settings', 'wc_marktplaats_category_mapping', array($this, 'sanitize_category_mapping'));
        register_setting('wc_marktplaats_settings', 'wc_marktplaats_default_shipping');
        register_setting('wc_marktplaats_settings', 'wc_marktplaats_default_description_prefix');
        register_setting('wc_marktplaats_settings', 'wc_marktplaats_default_description_suffix');
        register_setting('wc_marktplaats_settings', 'wc_marktplaats_category_cache_duration', 'intval');
    }

    /**
     * Sanitize category mapping
     */
    public function sanitize_category_mapping($input) {
        $new_input = array();
        if (isset($input) && is_array($input)) {
            foreach ($input as $key => $val) {
                $new_input[sanitize_text_field($key)] = array(
                    'cat' => isset($val['cat']) ? sanitize_text_field($val['cat']) : '',
                    'subcat' => isset($val['subcat']) ? sanitize_text_field($val['subcat']) : ''
                );
            }
        }
        return $new_input;
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('woocommerce_page_wc-marktplaats' !== $hook && 'edit.php' !== $hook && 'post.php' !== $hook) {
            return;
        }

        wp_enqueue_style('wc-marktplaats-admin', WC_MARKTPLAATS_PLUGIN_URL . 'assets/css/admin.css', array(), WC_MARKTPLAATS_VERSION);
        wp_enqueue_script('wc-marktplaats-admin', WC_MARKTPLAATS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), WC_MARKTPLAATS_VERSION, true);
        
        wp_localize_script('wc-marktplaats-admin', 'wc_marktplaats', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_marktplaats_nonce'),
            'refresh_categories_text' => __('Refresh Categories', 'wc-marktplaats'),
            'refreshing_text' => __('Refreshing...', 'wc-marktplaats'),
            'refreshed_text' => __('Categories refreshed!', 'wc-marktplaats'),
            'error_text' => __('Error refreshing categories', 'wc-marktplaats'),
            'loading_subcategories_text' => __('Loading subcategories...', 'wc-marktplaats'),
        ));
    }

    /**
     * Settings page content
     */
    public function settings_page() {
        include_once WC_MARKTPLAATS_PLUGIN_DIR . 'views/settings-page.php';
    }

    /**
     * Add meta box to product page
     */
    public function add_product_metabox() {
        add_meta_box(
            'wc_marktplaats_metabox',
            __('Marktplaats Posting', 'wc-marktplaats'),
            array($this, 'render_product_metabox'),
            'product',
            'side'
        );
    }

    /**
     * Render product meta box
     */
    public function render_product_metabox($post) {
        include_once WC_MARKTPLAATS_PLUGIN_DIR . 'views/product-metabox.php';
    }
    
    /**
     * Save metabox data
     */
    public function save_metabox_data($post_id) {
        // Check if our nonce is set and verify it
        if (!isset($_POST['wc_marktplaats_metabox_nonce']) || !wp_verify_nonce($_POST['wc_marktplaats_metabox_nonce'], 'wc_marktplaats_metabox')) {
            return;
        }
        
        // If this is an autosave, our form has not been submitted, so we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Make sure the user has permission to edit the product
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save category overrides if set
        if (isset($_POST['marktplaats_category_override'])) {
            update_post_meta($post_id, '_marktplaats_category_override', sanitize_text_field($_POST['marktplaats_category_override']));
        }
        
        if (isset($_POST['marktplaats_subcategory_override'])) {
            update_post_meta($post_id, '_marktplaats_subcategory_override', sanitize_text_field($_POST['marktplaats_subcategory_override']));
        }
    }

    /**
     * Add action to product list
     */
    public function add_post_row_action($actions, $post) {
        if ($post->post_type === 'product') {
            $marktplaats_id = get_post_meta($post->ID, '_marktplaats_id', true);
            
            if ($marktplaats_id) {
                $actions['update_marktplaats'] = '<a href="' . wp_nonce_url(admin_url('admin.php?page=wc-marktplaats&action=update&product_id=' . $post->ID), 'update_marktplaats_' . $post->ID) . '">' . __('Update on Marktplaats', 'wc-marktplaats') . '</a>';
            } else {
                $actions['post_to_marktplaats'] = '<a href="' . wp_nonce_url(admin_url('admin.php?page=wc-marktplaats&action=post&product_id=' . $post->ID), 'post_to_marktplaats_' . $post->ID) . '">' . __('Post to Marktplaats', 'wc-marktplaats') . '</a>';
            }
        }
        
        return $actions;
    }

    /**
     * Process the post to Marktplaats action
     */
    public function process_post_to_marktplaats() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'wc-marktplaats') {
            return;
        }
        
        if (!isset($_GET['action']) || !isset($_GET['product_id'])) {
            return;
        }
        
        $action = sanitize_text_field($_GET['action']);
        $product_id = intval($_GET['product_id']);
        
        if ($action === 'post' && check_admin_referer('post_to_marktplaats_' . $product_id)) {
            $result = $this->product_handler->post_product_to_marktplaats($product_id);
            
            if (is_wp_error($result)) {
                wp_redirect(add_query_arg(array(
                    'post_type' => 'product',
                    'marktplaats_error' => urlencode($result->get_error_message())
                ), admin_url('edit.php')));
                exit;
            } else {
                wp_redirect(add_query_arg(array(
                    'post_type' => 'product',
                    'marktplaats_success' => 1,
                    'product_id' => $product_id
                ), admin_url('edit.php')));
                exit;
            }
        } else if ($action === 'update' && check_admin_referer('update_marktplaats_' . $product_id)) {
            $result = $this->product_handler->update_product_on_marktplaats($product_id);
            
            if (is_wp_error($result)) {
                wp_redirect(add_query_arg(array(
                    'post_type' => 'product',
                    'marktplaats_error' => urlencode($result->get_error_message())
                ), admin_url('edit.php')));
                exit;
            } else {
                wp_redirect(add_query_arg(array(
                    'post_type' => 'product',
                    'marktplaats_updated' => 1,
                    'product_id' => $product_id
                ), admin_url('edit.php')));
                exit;
            }
        }
    }

    /**
     * Register bulk actions
     */
    public function register_bulk_actions($bulk_actions) {
        $bulk_actions['post_to_marktplaats'] = __('Post to Marktplaats', 'wc-marktplaats');
        $bulk_actions['update_on_marktplaats'] = __('Update on Marktplaats', 'wc-marktplaats');
        return $bulk_actions;
    }

    /**
     * Handle bulk actions
     */
    public function handle_bulk_actions($redirect_to, $action, $post_ids) {
        if ($action !== 'post_to_marktplaats' && $action !== 'update_on_marktplaats') {
            return $redirect_to;
        }
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($post_ids as $post_id) {
            if ($action === 'post_to_marktplaats') {
                $result = $this->product_handler->post_product_to_marktplaats($post_id);
            } else {
                $result = $this->product_handler->update_product_on_marktplaats($post_id);
            }
            
            if (is_wp_error($result)) {
                $error_count++;
            } else {
                $success_count++;
            }
        }
        
        $redirect_to = add_query_arg(array(
            'marktplaats_bulk_' . $action => 1,
            'marktplaats_bulk_success' => $success_count,
            'marktplaats_bulk_error' => $error_count
        ), $redirect_to);
        
        return $redirect_to;
    }

    /**
     * Admin notices
     */
    public function admin_notices() {
        if (isset($_GET['marktplaats_success']) && isset($_GET['product_id'])) {
            $product_id = intval($_GET['product_id']);
            $product = wc_get_product($product_id);
            $marktplaats_url = get_post_meta($product_id, '_marktplaats_url', true);
            
            echo '<div class="notice notice-success is-dismissible">';
            printf(
                '<p>' . __('Product "%s" has been successfully posted to Marktplaats.', 'wc-marktplaats') . ' ',
                esc_html($product->get_name())
            );
            
            if ($marktplaats_url) {
                echo '<a href="' . esc_url($marktplaats_url) . '" target="_blank">' . __('View Listing', 'wc-marktplaats') . '</a>';
            }
            
            echo '</p></div>';
        }
        
        if (isset($_GET['marktplaats_updated']) && isset($_GET['product_id'])) {
            $product_id = intval($_GET['product_id']);
            $product = wc_get_product($product_id);
            $marktplaats_url = get_post_meta($product_id, '_marktplaats_url', true);
            
            echo '<div class="notice notice-success is-dismissible">';
            printf(
                '<p>' . __('Product "%s" has been successfully updated on Marktplaats.', 'wc-marktplaats') . ' ',
                esc_html($product->get_name())
            );
            
            if ($marktplaats_url) {
                echo '<a href="' . esc_url($marktplaats_url) . '" target="_blank">' . __('View Listing', 'wc-marktplaats') . '</a>';
            }
            
            echo '</p></div>';
        }
        
        if (isset($_GET['marktplaats_error'])) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . esc_html(urldecode($_GET['marktplaats_error'])) . '</p></div>';
        }
        
        if (isset($_GET['marktplaats_bulk_post_to_marktplaats']) || isset($_GET['marktplaats_bulk_update_on_marktplaats'])) {
            $action = isset($_GET['marktplaats_bulk_post_to_marktplaats']) ? 'posted' : 'updated';
            $success_count = isset($_GET['marktplaats_bulk_success']) ? intval($_GET['marktplaats_bulk_success']) : 0;
            $error_count = isset($_GET['marktplaats_bulk_error']) ? intval($_GET['marktplaats_bulk_error']) : 0;
            
            if ($success_count > 0) {
                echo '<div class="notice notice-success is-dismissible">';
                printf(
                    '<p>' . _n('%d product was successfully %s on Marktplaats.', '%d products were successfully %s on Marktplaats.', $success_count, 'wc-marktplaats') . '</p>',
                    $success_count,
                    $action
                );
                echo '</div>';
            }
            
            if ($error_count > 0) {
                echo '<div class="notice notice-error is-dismissible">';
                printf(
                    '<p>' . _n('%d product could not be %s on Marktplaats.', '%d products could not be %s on Marktplaats.', $error_count, 'wc-marktplaats') . ' ' . __('Please check your settings and try again.', 'wc-marktplaats') . '</p>',
                    $error_count,
                    $action
                );
                echo '</div>';
            }
        }
    }
}