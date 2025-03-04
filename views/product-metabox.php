<?php
/**
 * Product metabox view
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$product_id = $post->ID;
$marktplaats_id = get_post_meta($product_id, '_marktplaats_id', true);
$marktplaats_url = get_post_meta($product_id, '_marktplaats_url', true);
$posted_date = get_post_meta($product_id, '_marktplaats_posted_date', true);
$updated_date = get_post_meta($product_id, '_marktplaats_updated_date', true);

// Get category overrides
$category_override = get_post_meta($product_id, '_marktplaats_category_override', true);
$subcategory_override = get_post_meta($product_id, '_marktplaats_subcategory_override', true);

// Get categories
$marktplaats_categories = $this->category_manager->get_marktplaats_categories();

// Get subcategories if category is selected
$marktplaats_subcategories = array();
if (!empty($category_override)) {
    $marktplaats_subcategories = $this->category_manager->get_marktplaats_subcategories($category_override);
}
?>

<div class="marktplaats-metabox-container">
    <?php if ($marktplaats_id && $marktplaats_url): ?>
        <div class="marktplaats-status">
            <p>
                <strong><?php _e('Status:', 'wc-marktplaats'); ?></strong> 
                <span class="marktplaats-posted"><?php _e('Posted on Marktplaats', 'wc-marktplaats'); ?></span>
            </p>
            
            <p>
                <strong><?php _e('Listing ID:', 'wc-marktplaats'); ?></strong> 
                <?php echo esc_html($marktplaats_id); ?>
            </p>
            
            <?php if ($posted_date): ?>
                <p>
                    <strong><?php _e('Posted Date:', 'wc-marktplaats'); ?></strong> 
                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($posted_date))); ?>
                </p>
            <?php endif; ?>
            
            <?php if ($updated_date): ?>
                <p>
                    <strong><?php _e('Last Updated:', 'wc-marktplaats'); ?></strong> 
                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($updated_date))); ?>
                </p>
            <?php endif; ?>
            
            <p>
                <a href="<?php echo esc_url($marktplaats_url); ?>" target="_blank" class="button"><?php _e('View Listing', 'wc-marktplaats'); ?></a>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wc-marktplaats&action=update&product_id=' . $product_id), 'update_marktplaats_' . $product_id); ?>" class="button"><?php _e('Update Listing', 'wc-marktplaats'); ?></a>
            </p>
        </div>
    <?php else: ?>
        <div class="marktplaats-status">
            <p>
                <strong><?php _e('Status:', 'wc-marktplaats'); ?></strong> 
                <span class="marktplaats-not-posted"><?php _e('Not yet posted to Marktplaats', 'wc-marktplaats'); ?></span>
            </p>
            
            <p>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wc-marktplaats&action=post&product_id=' . $product_id), 'post_to_marktplaats_' . $product_id); ?>" class="button button-primary"><?php _e('Post to Marktplaats', 'wc-marktplaats'); ?></a>
            </p>
        </div>
    <?php endif; ?>
    
    <div class="marktplaats-category-override">
        <h4><?php _e('Category Override (Optional)', 'wc-marktplaats'); ?></h4>
        <p class="description"><?php _e('You can override the mapped category for this specific product.', 'wc-marktplaats'); ?></p>
        
        <p>
            <label for="marktplaats_category_override"><?php _e('Category:', 'wc-marktplaats'); ?></label><br>
            <select id="marktplaats_category_override" name="marktplaats_category_override" class="marktplaats-category">
                <option value=""><?php _e('-- Use Default Mapping --', 'wc-marktplaats'); ?></option>
                <?php foreach ($marktplaats_categories as $cat_id => $cat_name): ?>
                    <option value="<?php echo esc_attr($cat_id); ?>" <?php selected($category_override, $cat_id); ?>><?php echo esc_html($cat_name); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <p>
            <label for="marktplaats_subcategory_override"><?php _e('Subcategory:', 'wc-marktplaats'); ?></label><br>
            <select id="marktplaats_subcategory_override" name="marktplaats_subcategory_override" class="marktplaats-subcategory">
                <option value=""><?php echo empty($category_override) ? __('-- First Select Category --', 'wc-marktplaats') : __('-- Select Subcategory --', 'wc-marktplaats'); ?></option>
                <?php foreach ($marktplaats_subcategories as $subcat_id => $subcat_name): ?>
                    <option value="<?php echo esc_attr($subcat_id); ?>" <?php selected($subcategory_override, $subcat_id); ?>><?php echo esc_html($subcat_name); ?></option>
                <?php endforeach; ?>
            </select>
            <span class="spinner subcategory-spinner" style="float: none; display: none;"></span>
        </p>
    </div>
</div>

<?php wp_nonce_field('wc_marktplaats_metabox', 'wc_marktplaats_metabox_nonce'); ?>