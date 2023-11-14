Zaprite Extension for Woo
=========================

**Accept Bitcoin and Lightning**

Start accepting Bitcoin and Lightning today. Powered by Zaprite

This plugin allows stores that use Wordpress WooCommerce shopping cart system to accept Bitcoin and Bitcoin through Lightning Network via Zaprite.

*In order to use this plugin you have to create an account on [https://zaprite.com](https://zaprite.com)*

## Installation

1. Upload the zipped `zaprite-payment-gateway` folder to the `/wp-content/plugins/` directory (or in wordpress > add new > upload).
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to WooCommerce > Settings > Payments.
4. Click on 'Zaprite' to configure the payment gateway settings.

## Local development

To develop locally

1. Download and install Local by Flywheel here: https://localwp.com/
2. Create a wordpress site and install the Woocommerce plugin
3. Goto settings > permalinks > Permalink structure	> (make sure this is set to 'Day and name')
4. Before cloning this repo we want to make sure you are in the working directory for the workpress plugins.
To get the directory goto "Local by Flywheel" and click "Open in shell" at the top. The plugin directory should be at `app/public/wp-content/plugins`.
5. `cd wp-content/plugins`
6. Then run `git clone https://github.com/ZapriteApp/zaprite-for-woocommerce.git`
7. In the file `zaprite.php` edit this code at the top with your settings
```
define('ZAPRITE_ENV', 'dev');
define('ZAPRITE_DEV_PATH', 'http://localhost:3000');
```
8. In wordpress admin dashboard `http://yoursite.local/wp-admin` goto plugin > installed plugins > make sure Zaprite is activated
9. goto woocommerce > settings > Payments > enable and configure Zaprite
10. Make sure to get Zaprite api key from http://localhost:3000/org/{YOUR_ORG_ID}/connections/woo
11. To turn on debugging Edit the `wp-config.php` File. Look for the line that says `define('WP_DEBUG', false);`. If it's not there, you'll need to add it. And change it to true.
12. Then restart your site. This should create a debug.log file in `wp-content`
13. Now you can test the plugin by creating an order in your shop.