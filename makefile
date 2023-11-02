start:
	docker compose up -d

install-woo:
	docker exec -it woocommerce-wordpress-1 /bin/bash
# curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
# php wp-cli.phar --info
# chmod +x wp-cli.phar
# mv wp-cli.phar /usr/local/bin/wp
# wp core install --allow-root --url="http://localhost:8000" --title="WooSite" --admin_user="admin" --admin_email="ntheile@gmail.com"
# wp plugin install woocommerce --activate --allow-root

