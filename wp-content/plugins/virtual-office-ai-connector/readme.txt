=== MyMomo - Connector ===
Contributors: Cornerstone & Compass
Plugin URI: https://virtualofficeai.com.au
Tags: virtual-office-ai, connector, api, automation, content-management, seo
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your WordPress site to MyMomo for AI-powered content management, SEO optimization, and site automation.

== Description ==

MyMomo - Connector is a secure bridge between your WordPress site and MyMomo. This plugin enables remote site management with enterprise-grade security features.

**Key Features:**

- Generate unique API keys for secure authentication
- Rate limiting (60 requests per minute)
- Full REST API for content management
- Posts and Pages management (create, read, update)
- Media upload with base64 encoding
- Plugin activation and deactivation control
- Theme customization via Customizer API
- Menu management
- WooCommerce integration (products, orders, stats)
- Admin dashboard with easy setup instructions

**Secure API Endpoints:**

- GET /wp-json/voa/v1/status - Site health check
- GET /wp-json/voa/v1/posts - List posts (paginated)
- POST /wp-json/voa/v1/posts - Create posts
- PUT /wp-json/voa/v1/posts/{id} - Update posts
- GET /wp-json/voa/v1/pages - List pages
- POST /wp-json/voa/v1/pages - Create pages
- PUT /wp-json/voa/v1/pages/{id} - Update pages
- GET /wp-json/voa/v1/media - List media
- POST /wp-json/voa/v1/media - Upload media (base64)
- GET /wp-json/voa/v1/plugins - List plugins
- POST /wp-json/voa/v1/plugins/{slug}/activate - Activate plugins
- POST /wp-json/voa/v1/plugins/{slug}/deactivate - Deactivate plugins
- GET /wp-json/voa/v1/theme - Get active theme info
- PUT /wp-json/voa/v1/theme/customizer - Update customizer settings
- GET /wp-json/voa/v1/menus - List menus
- PUT /wp-json/voa/v1/menus/{id} - Update menu items
- GET /wp-json/voa/v1/woocommerce/products - List products
- POST /wp-json/voa/v1/woocommerce/products - Create products
- PUT /wp-json/voa/v1/woocommerce/products/{id} - Update products
- GET /wp-json/voa/v1/woocommerce/orders - List orders
- GET /wp-json/voa/v1/woocommerce/stats - Sales statistics
- POST /wp-json/voa/v1/execute - Execute whitelisted actions

== Installation ==

1. Upload the plugin folder to your WordPress site: `/wp-content/plugins/`
2. Activate the plugin through the WordPress Plugins menu
3. Go to Settings > MyMomo
4. Copy your API Key and Site URL
5. Enter these credentials in MyMomo settings
6. Test the connection

**That's it!** Your WordPress site is now connected to MyMomo.

== Getting Started ==

After activation, navigate to Settings > MyMomo in your WordPress admin panel:

1. **API Key**: Your unique authentication token (automatically generated)
2. **Site URL**: The URL of your WordPress site
3. **Last Connection**: Shows when MyMomo last connected
4. **Regenerate Key**: Create a new API key if needed (invalidates old key)

All API requests must include the API key in the `X-VOA-Key` header for authentication.

== Frequently Asked Questions ==

= Is my site secure with this plugin? =

Yes. The plugin uses:
- Cryptographically secure API keys (256-bit)
- Header-based authentication validation
- Rate limiting (60 requests per minute)
- Input sanitization and output escaping
- WordPress nonce verification for admin actions
- Server-to-server communication support

= Can I regenerate my API key? =

Yes. Go to Settings > MyMomo and click "Regenerate API Key". The old key will be invalidated immediately.

= What about WooCommerce? =

The plugin includes full WooCommerce integration if WooCommerce is installed:
- List products with filtering
- Create and update products
- View orders and statistics
- Graceful fallback if WooCommerce is not active

= How do I troubleshoot connection issues? =

1. Verify the API key is correct in MyMomo settings
2. Check that the Site URL matches your WordPress installation
3. Ensure the plugin is activated in WordPress
4. Verify your hosting supports REST API (most modern hosts do)
5. Check server logs for detailed error messages

= Can I use this with multisite WordPress? =

Yes, the plugin is multisite compatible. Each site gets its own unique API key.

= What if I uninstall the plugin? =

Your API key and settings are preserved if you deactivate the plugin. Uninstalling will remove all plugin data.

= Does this support content scheduling? =

The API supports post statuses including 'draft' and 'pending'. You can set scheduled publishing through other means (e.g., WordPress's native scheduling or third-party plugins).

= What input sanitization is performed? =

The plugin uses WordPress security functions:
- `sanitize_text_field()` for text inputs
- `sanitize_key()` for option names
- `wp_kses_post()` for HTML content
- `esc_url_raw()` for URLs
- `absint()` for integers
- `array_map()` with type-safe functions for arrays

= Can I customize the API endpoints? =

The endpoints and responses are standardized for compatibility with MyMomo. Custom modifications may break integration.

= Is logging available? =

Connection status is logged via the "Last Connection" transient in the admin panel. Enable WordPress debug mode for detailed error logging.

== Changelog ==

= 1.15.0 =
* Merged behaviour insights beacon + config endpoint (previously only in the MyMomo bundled copy)
* New AI visibility controls: serve /llms.txt and manage AI crawler rules in robots.txt, pushed from MyMomo
* Auto-updater now checks api.virtualofficeai.com.au and reports the site host for update telemetry


= 1.0.0 =
* Initial plugin release
* 22 REST API endpoints
* Full post, page, and media management
* WooCommerce integration
* Plugin and theme management
* Menu management
* Rate limiting and security features
* Admin settings dashboard

== Support ==

For support, issues, or feature requests, visit: https://virtualofficeai.com.au

== Credits ==

Developed by Cornerstone & Compass
https://cornerstoneandcompass.com

== License ==

This plugin is licensed under the GPL v2 or later.
