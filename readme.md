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

1. Open the file `html/wp-content/plugins/zaprite-payment-gateway/zaprite.php`
2. Edit this code at the top with your settings
```
define('ZAPRITE_ENV', 'dev');
define('ZAPRITE_DEV_PATH', 'http://localhost:3000');
define('ZAPRITE_DOCKER_PATH', 'http://host.docker.internal:3000'); // use http://host.docker.internal:3000 if in docker env, otherwise http://localhost:3000
```
3. `make start` or `docker compose up -d`
4. open http://localhost:8000/wp-admin (admin,password)
5. install the woocommerce plugin
6. goto settings > permalinks > Permalink structure	> (make sure this is set to 'Day and name')
7. goto plugin > installed plugins > make sure Zaprite is activated
8. goto woocommerce > settings > Payments > enable and configure Zaprite
9. Make sure to get Zaprite api key from http://localhost:3000/org/{YOUR_ORG_ID}/connections/woo
