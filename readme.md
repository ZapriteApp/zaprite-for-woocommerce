Install
=====

https://developer.woocommerce.com/extension-developer-guide/creating-your-first-extension/

1. install the wp-cli on your local machine
```
brew install php
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
php wp-cli.phar --info
```

2. run this code
```
make start
```

3. install woocommerce plugin
```
docker exec -it woocommerce-wordpress-1 /bin/bash
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
php wp-cli.phar --info
chmod +x wp-cli.phar
mv wp-cli.phar /usr/local/bin/wp
wp core install --allow-root --url="http://localhost:8000" --title="WooSite" --admin_user="admin" --admin_email="ntheile@gmail.com" --admin_password="password"
wp plugin install woocommerce --activate --allow-root

wp user list --allow-root
wp user update admin --user_pass=password --allow-root

```

4. open http://localhost:8000/wp-admin


5. https://developer.woocommerce.com/extension-developer-guide/creating-your-first-extension/
