<?php
/**
 * Admin settings page view
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php _e('WooCommerce to Marktplaats Integration', 'wc-marktplaats'); ?></h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('wc_marktplaats_settings'); ?>
        <?php do_settings_sections('wc_marktplaats_settings'); ?>
        
        <div class="card">
            <h2><?php _e('Marktplaats Account Settings', 'wc-marktplaats'); ?></h2>
            <p><?php _e('Enter your Marktplaats.nl account credentials to enable automatic posting.', 'wc-marktplaats'); ?></p>
            
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Marktplaats Username/Email', 'wc-marktplaats'); ?></th>
                    <td><input type="text" name="wc_marktplaats_username" value="<?php echo esc_attr(get_option('wc_marktplaats_username')); ?>" class="regular-text" required /></td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><?php _e('Marktplaats Password', 'wc-marktplaats'); ?></th>
                    <td><input type="password" name="wc_marktplaats_password" value="<?php echo esc_attr(get_option('wc_marktplaats_password')); ?>" class="regular-text" required /></td>
                </tr>
            </table>
        </div>
        
        <div class="card">
            <h2><?php _e('Default Listing Settings', 'wc-marktplaats'); ?></h2>
            <p><?php _e('Configure default settings for your Marktplaats listings.', 'wc-marktplaats'); ?></p>
            
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Default Shipping Method', 'wc-marktplaats'); ?></th>
                    <td>
                        <select name="wc_marktplaats_default_shipping">
                            <option value="pickup" <?php selected(get_option('wc_marktplaats_default_shipping'), 'pickup'); ?>><?php _e('Pickup Only', 'wc-marktplaats'); ?></option>
                            <option value="shipping" <?php selected(get_option('wc_marktplaats_default_shipping'), 'shipping'); ?>><?php _e('Shipping Only', 'wc-marktplaats'); ?></option>
                            <option value="both" <?php selected(get_option('wc_marktplaats_default_shipping'), 'both'); ?>><?php _e('Both Pickup and Shipping', 'wc-marktplaats'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><?php _e('Default Description Prefix', 'wc-marktplaats'); ?></th>
                    <td>
                        <textarea name="wc_marktplaats_default_description_prefix" rows="4" class="large-text"><?php echo esc_textarea(get_option('wc_marktplaats_default_description_prefix')); ?></textarea>
                        <p class="description"><?php _e('Text to add before the product description.', 'wc-marktplaats'); ?></p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><?php _e('Default Description Suffix', 'wc-marktplaats'); ?></th>
                    <td>
                        <textarea name="wc_marktplaats_default_description_suffix" rows="4" class="large-text"><?php echo esc_textarea(get_option('wc_marktplaats_default_description_suffix')); ?></textarea>
                        <p class="description"><?php _e('Text to add after the product description.', 'wc-marktplaats'); ?></p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><?php _e('Category Cache Duration (days)', 'wc-marktplaats'); ?></th>
                    <td>
                        <input type="number" name="wc_marktplaats_category_cache_duration" value="<?php echo esc_attr(get_option('wc_marktplaats_category_cache_duration', 7)); ?>" min="1" max="30" class="small-text" />
                        <p class="description"><?php _e('How long to cache Marktplaats categories (in days).', 'wc-marktplaats'); ?></p>
                        <button type="button" id="refresh-marktplaats-categories" class="button"><?php _e('Refresh Categories Now', 'wc-marktplaats'); ?></button>
                        <span id="refresh-categories-status" style="margin-left: 10px;"></span>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="card">
            <h2><?php _e('Category Mapping', 'wc-marktplaats'); ?></h2>
            <p><?php _e('Map your WooCommerce product categories to Marktplaats categories.', 'wc-marktplaats'); ?></p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('WooCommerce Category', 'wc-marktplaats'); ?></th>
                        <th><?php _e('Marktplaats Category', 'wc-marktplaats'); ?></th>
                        <th><?php _e('Marktplaats Subcategory', 'wc-marktplaats'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $category_mapping = get_option('wc_marktplaats_category_mapping', array());
                    $woo_categories = get_terms(array(
                        'taxonomy' => 'product_cat',
                        'hide_empty' => false,
                    ));
                    
                    // Get Marktplaats categories
                    $marktplaats_categories = $this->category_manager->get_marktplaats_categories();
                    
                    foreach ($woo_categories as $woo_cat) {
                        $selected_cat = isset($category_mapping[$woo_cat->term_id]['cat']) ? $category_mapping[$woo_cat->term_id]['cat'] : '';
                        $selected_subcat = isset($category_mapping[$woo_cat->term_id]['subcat']) ? $category_mapping[$woo_cat->term_id]['subcat'] : '';
                        
                        // Get subcategories if a category is selected
                        $marktplaats_subcategories = array();
                        if (!empty($selected_cat)) {
                            $marktplaats_subcategories = $this->category_manager->get_marktplaats_subcategories($selected_cat);
                        }
                        ?>
                        <tr>
                            <td><?php echo esc_html($woo_cat->name); ?></td>
                            <td>
                                <select name="wc_marktplaats_category_mapping[<?php echo esc_attr($woo_cat->term_id); ?>][cat]" class="marktplaats-category" data-woo-category="<?php echo esc_attr($woo_cat->term_id); ?>">
                                    <option value="">-- <?php _e('Select Category', 'wc-marktplaats'); ?> --</option>
                                    <?php foreach ($marktplaats_categories as $cat_id => $cat_name) : ?>
                                        <option value="<?php echo esc_attr($cat_id); ?>" <?php selected($selected_cat, $cat_id); ?>><?php echo esc_html($cat_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="wc_marktplaats_category_mapping[<?php echo esc_attr($woo_cat->term_id); ?>][subcat]" class="marktplaats-subcategory" data-woo-category="<?php echo esc_attr($woo_cat->term_id); ?>">
                                    <option value="">-- <?php echo empty($selected_cat) ? __('First Select Category', 'wc-marktplaats') : __('Select Subcategory', 'wc-marktplaats'); ?> --</option>
                                    <?php foreach ($marktplaats_subcategories as $subcat_id => $subcat_name) : ?>
                                        <option value="<?php echo esc_attr($subcat_id); ?>" <?php selected($selected_subcat, $subcat_id); ?>><?php echo esc_html($subcat_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="spinner subcategory-spinner" style="float: none; display: none;"></span>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php submit_button(); ?>
    </form>
</div>