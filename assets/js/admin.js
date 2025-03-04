/**
 * WooCommerce to Marktplaats Admin JavaScript
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initCategoryRefresh();
        initCategorySelectors();
    });
    
    /**
     * Initialize category refresh button
     */
    function initCategoryRefresh() {
        var refreshButton = $('#refresh-marktplaats-categories');
        var statusText = $('#refresh-categories-status');
        
        if (refreshButton.length) {
            refreshButton.on('click', function(e) {
                e.preventDefault();
                
                var originalText = refreshButton.text();
                refreshButton.prop('disabled', true).text(wc_marktplaats.refreshing_text);
                statusText.text('');
                
                // Send AJAX request to refresh categories
                $.ajax({
                    url: wc_marktplaats.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'refresh_marktplaats_categories',
                        nonce: wc_marktplaats.nonce
                    },
                    success: function(response) {
                        refreshButton.prop('disabled', false).text(originalText);
                        
                        if (response.success) {
                            statusText.text(wc_marktplaats.refreshed_text + ' (' + response.data.count + ')');
                            
                            // Reload page to show new categories
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        } else {
                            statusText.text(wc_marktplaats.error_text);
                        }
                    },
                    error: function() {
                        refreshButton.prop('disabled', false).text(originalText);
                        statusText.text(wc_marktplaats.error_text);
                    }
                });
            });
        }
    }
    
    /**
     * Initialize category and subcategory selectors
     */
    function initCategorySelectors() {
        $('.marktplaats-category').each(function() {
            var categorySelect = $(this);
            var wooCategory = categorySelect.data('woo-category');
            var subcategorySelect = getSubcategorySelect(categorySelect);
            var spinner = getSpinner(subcategorySelect);
            
            // Handle category change
            categorySelect.on('change', function() {
                var categoryId = $(this).val();
                
                // Reset subcategory dropdown
                subcategorySelect.html('<option value="">-- ' + (categoryId ? wc_marktplaats.select_subcategory_text : wc_marktplaats.select_category_first_text) + ' --</option>');
                
                if (categoryId) {
                    // Show spinner
                    spinner.show();
                    
                    // Fetch subcategories
                    $.ajax({
                        url: wc_marktplaats.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'get_marktplaats_subcategories',
                            nonce: wc_marktplaats.nonce,
                            category_id: categoryId
                        },
                        success: function(response) {
                            spinner.hide();
                            
                            if (response.success && response.data.subcategories) {
                                var subcategories = response.data.subcategories;
                                
                                // Add subcategories to dropdown
                                $.each(subcategories, function(id, name) {
                                    subcategorySelect.append('<option value="' + id + '">' + name + '</option>');
                                });
                            }
                        },
                        error: function() {
                            spinner.hide();
                        }
                    });
                }
            });
        });
    }
    
    /**
     * Get subcategory select element associated with a category select
     */
    function getSubcategorySelect(categorySelect) {
        var wooCategory = categorySelect.data('woo-category');
        
        if (wooCategory) {
            // In settings page with multiple categories
            return $('.marktplaats-subcategory[data-woo-category="' + wooCategory + '"]');
        } else {
            // In product metabox
            return categorySelect.closest('div, tr, form').find('.marktplaats-subcategory');
        }
    }
    
    /**
     * Get spinner element associated with a subcategory select
     */
    function getSpinner(subcategorySelect) {
        return subcategorySelect.siblings('.subcategory-spinner');
    }
    
})(jQuery);