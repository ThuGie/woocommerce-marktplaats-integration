# WooCommerce to Marktplaats Integration

This WordPress plugin allows you to automatically post your WooCommerce products to Marktplaats.nl.

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

## Integration Methods

This plugin offers two methods of integration with Marktplaats.nl:

### 1. Web Automation (Default)

The default method uses web automation to interact with Marktplaats.nl through their website. This method:

- Requires no additional API access
- Works for any Marktplaats user
- May be less reliable due to website changes

### 2. Official API Integration (Optional)

Marktplaats.nl offers an official API which can be used for more reliable integration. This method:

- Requires registration as a Marktplaats API partner
- Provides more stable integration
- Follows official API standards (OAuth 2.0)

To use the API integration:

1. Register as a developer/partner with Marktplaats
2. Obtain API credentials (client ID and client secret)
3. Update the plugin settings with your API credentials

For more information on the Marktplaats API:
- API Documentation: https://api.marktplaats.nl/docs/v1/index.html
- GitHub Repository: https://github.com/ecg-marktplaats/marktplaats-api

## Customization

### Web Automation Implementation

To implement the actual browser automation:

1. Edit the `class-wc-marktplaats-product-handler.php` file
2. Replace the `simulate_marktplaats_post()` method with actual implementation
3. Use the provided `browser_automation_guide()` method as a reference

### API Implementation

To implement the API integration:

1. Create a new `class-wc-marktplaats-api-handler.php` file
2. Implement OAuth 2.0 authentication
3. Use the API endpoints for categories, advertisements, and users

## License

This plugin is licensed under the GPL v2 or later.