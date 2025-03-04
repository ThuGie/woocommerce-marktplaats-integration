# WooCommerce to Marktplaats Integration - Directory Structure

This document outlines the structure of the plugin files and their purpose.

```
woocommerce-marktplaats-integration/
├── woocommerce-marktplaats.php          # Main plugin file (entry point)
├── README.md                            # Plugin documentation
├── STRUCTURE.md                         # This file
│
├── includes/                            # Core PHP classes
│   ├── class-wc-marktplaats-integration.php      # Main integration class
│   ├── class-wc-marktplaats-category-manager.php # Category management
│   ├── class-wc-marktplaats-scraper.php          # Web scraping for categories
│   └── class-wc-marktplaats-product-handler.php  # Posting products to Marktplaats
│
├── views/                               # Template files
│   ├── settings-page.php                # Settings page template
│   └── product-metabox.php              # Product metabox template
│
└── assets/                              # Static assets
    ├── css/
    │   └── admin.css                    # Admin styles
    └── js/
        └── admin.js                     # Admin JavaScript
```

## File Descriptions

### Core Files

- **woocommerce-marktplaats.php**: The main plugin file that sets up the hooks, includes required files, and defines constants.

### Include Files

- **class-wc-marktplaats-integration.php**: The main integration class that handles admin menus, settings registration, and admin interactions.

- **class-wc-marktplaats-category-manager.php**: Manages the Marktplaats category cache and mapping between WooCommerce and Marktplaats categories.

- **class-wc-marktplaats-scraper.php**: Handles web scraping of Marktplaats.nl to extract category and subcategory data.

- **class-wc-marktplaats-product-handler.php**: Handles the preparation of product data and posting to Marktplaats through web automation.

### View Templates

- **settings-page.php**: Template for the settings page under WooCommerce > Marktplaats.

- **product-metabox.php**: Template for the product metabox shown on individual product edit pages.

### Assets

- **admin.css**: Styling for the admin interface including settings page and product metabox.

- **admin.js**: JavaScript for handling dynamic category loading and AJAX interactions in the admin area.

## Implementation Details

The plugin provides a framework for posting WooCommerce products to Marktplaats.nl through web automation. The actual automation is simulated in this version for demonstration purposes (see `simulate_marktplaats_post()` method in the product handler class).

For a production implementation, you would need to implement the browser automation logic based on the guide provided in the `browser_automation_guide()` method, using a library like PHP-WebDriver with Selenium.