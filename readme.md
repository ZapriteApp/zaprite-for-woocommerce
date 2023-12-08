# Zaprite Extension for Woo

**Accept Bitcoin and Lightning**

Start accepting Bitcoin and Lightning today. Powered by Zaprite

This plugin allows stores that use Wordpress WooCommerce shopping cart system to accept Bitcoin and Bitcoin through Lightning Network via Zaprite.

_In order to use this plugin you have to create an account on [https://zaprite.com](https://zaprite.com)_

## Installation

1. Upload the zipped `zaprite-payment-gateway` folder to the `/wp-content/plugins/` directory (or in wordpress > add new > upload).
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to WooCommerce > Settings > Payments.
4. Click on 'Zaprite' to configure the payment gateway settings.

## Local development

To develop locally, you can run wordpress locally with docker. This will run wordpress on port 8000 with debugging enabled. It will also mount your local `./plugin` directly in the wordpress `plugins`` directory so that you can edit and see the result of your changes directly in wordpress.

First, you'll need to run the docker containers:

```
cd wordpress-docker
docker-compose up -d
```

Then you'll need to:

1. `open http://localhost:8000`
1. Follow the WordPress installation instructions
1. Install and configure the WooCommerce plugin: http://localhost:8000/wp-admin/plugin-install.php?s=woocommerce&tab=search&type=term
1. Add a product in woo commerce so that you can test payments
1. Activate Zaprite plugin: http://localhost:8000/wp-admin/plugins.php
1. Confiture zaprite plugin: http://localhost:8000/wp-admin/admin.php?page=wc-settings&tab=checkout&section=zaprite
   1. Check "Enable payments via Zaprite Checkout"
   1. Enter your Zaprite API key
1. Goto [settings > permalinks](http://localhost:8000/wp-admin/options-permalink.php) > Permalink structure > (make sure this is set to 'Day and name')

Now you can test the plugin by creating an order in your shop: http://localhost:8000/shop/
