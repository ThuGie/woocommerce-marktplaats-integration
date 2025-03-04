# WooCommerce to Marktplaats Integration

This WordPress plugin allows you to automatically post your WooCommerce products to Marktplaats.nl through web automation.

## Features

- Automatically post WooCommerce products to Marktplaats.nl
- Map WooCommerce categories to Marktplaats categories
- Override categories for specific products
- Dynamically fetch and cache Marktplaats categories
- Bulk actions to post or update multiple products at once
- Track which products are posted to Marktplaats

## Installation

1. Upload the `woocommerce-marktplaats-integration` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'WooCommerce' > 'Marktplaats' to configure your settings

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.0 or higher

## Configuration

### Account Settings

1. Go to 'WooCommerce' > 'Marktplaats'
2. Enter your Marktplaats username/email and password
3. Configure default shipping options and description text
4. Map your WooCommerce product categories to Marktplaats categories
5. Save your settings

### Posting Products

You can post products to Marktplaats in several ways:

- **Individual Products**: Edit a product and use the "Marktplaats Posting" meta box on the right
- **Products List**: Use the "Post to Marktplaats" action link in the products list
- **Bulk Actions**: Select multiple products and use the "Post to Marktplaats" bulk action

## Web Automation

This plugin uses web automation to interact with Marktplaats.nl since there is no official API.

In a production environment, you would need to implement the actual browser automation functionality. There are several options:

1. **PHP-WebDriver with Selenium**: Requires a Selenium server
2. **Puppeteer via Node.js bridge**: Requires Node.js
3. **Chrome extension**: A companion extension to handle the automation

The plugin currently includes a template for implementing the automation with PHP-WebDriver, but actual implementation would depend on your server environment.

## Customization

### Browser Automation

To implement the actual browser automation:

1. Edit the `class-wc-marktplaats-product-handler.php` file
2. Replace the `simulate_marktplaats_post()` method with actual implementation
3. Use the provided `browser_automation_guide()` method as a reference

### Category Scraping

The category scraping functionality might need adjustments based on Marktplaats.nl's HTML structure:

1. Edit the `class-wc-marktplaats-scraper.php` file
2. Adjust the XPath expressions in `fetch_categories()` and `fetch_subcategories()` methods

## License

This plugin is licensed under the GPL v2 or later.