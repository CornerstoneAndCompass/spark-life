# MyMomo - WordPress Plugin

## Files

### virtual-office-ai-connector.php (1,899 lines, 50KB)
The main plugin file containing all functionality in a single, production-ready PHP file.

**Key Features Implemented:**

#### Security
- API key generation on activation (cryptographically secure, 256-bit)
- API key validation via X-VOA-Key header
- Request origin/referer validation
- Rate limiting: 60 requests per minute per IP
- Input sanitization and output escaping
- Nonce verification for admin actions

#### REST API Endpoints (22 total)

**Site Management:**
- GET /wp-json/voa/v1/status - Health check with system info

**Posts & Pages:**
- GET /posts - List posts (paginated, filterable)
- POST /posts - Create posts
- PUT /posts/{id} - Update posts
- GET /pages - List pages
- POST /pages - Create pages
- PUT /pages/{id} - Update pages

**Media:**
- GET /media - List media (paginated)
- POST /media - Upload via base64 encoding

**Plugins:**
- GET /plugins - List installed plugins
- POST /plugins/{slug}/activate - Activate plugin
- POST /plugins/{slug}/deactivate - Deactivate plugin

**Themes & Customization:**
- GET /theme - Get active theme info
- PUT /theme/customizer - Update customizer settings

**Menus:**
- GET /menus - List all menus and items
- PUT /menus/{id} - Update menu items

**WooCommerce (if installed):**
- GET /woocommerce/products - List products (paginated)
- POST /woocommerce/products - Create products
- PUT /woocommerce/products/{id} - Update products
- GET /woocommerce/orders - List recent orders
- GET /woocommerce/stats - Sales statistics

**Actions:**
- POST /execute - Execute whitelisted actions (update_option, create_menu_item, flush_rewrite_rules, update_nav_menu)

#### Admin Interface
- Settings page under "Settings > MyMomo"
- Display API key with copy-to-clipboard button
- Display site URL with copy button
- Show last connection time
- Regenerate API key with confirmation
- Clean, professional WordPress styling

#### Plugin Header
```
Plugin Name: MyMomo - Connector
Plugin URI: https://virtualofficeai.com.au
Description: Connect your WordPress site to MyMomo
Version: 1.0.0
Author: Cornerstone & Compass
License: GPL v2 or later
Requires at least: 5.8
Requires PHP: 7.4
```

### readme.txt (162 lines, 5.8KB)
Standard WordPress plugin readme with:
- Plugin description and features
- Installation instructions
- Getting started guide
- 10-question FAQ section
- Complete changelog
- Support and credit information
- License details

## Installation Instructions for Users

1. Upload `virtual-office-ai-connector.php` to `/wp-content/plugins/virtual-office-ai-connector/`
2. Activate the plugin in WordPress admin
3. Go to Settings > MyMomo
4. Copy the API Key and Site URL
5. Enter these in the MyMomo dashboard
6. Test the connection

## Security Notes

- API keys are stored securely in wp_options
- All REST endpoints require valid API key in X-VOA-Key header
- Rate limiting prevents abuse (60 requests/minute per IP)
- All user input is sanitized before use
- All output is escaped for security
- WooCommerce endpoints gracefully handle missing plugin
- Admin actions use WordPress nonces

## API Authentication

All requests must include:
```
Header: X-VOA-Key: <API_KEY>
```

The API key is generated on plugin activation and stored in the WordPress database.

## Deployment

The plugin is ready for production use:
- Single PHP file for easy distribution
- No external dependencies
- Compatible with WordPress 5.8+
- PHP 7.4+ support
- Multisite compatible
- WooCommerce optional integration

## Code Quality

- WordPress coding standards compliant
- Proper function namespacing (voa_ prefix)
- Comprehensive error handling
- Input validation and sanitization
- Output escaping for all displayed data
- Proper use of WordPress hooks and APIs
