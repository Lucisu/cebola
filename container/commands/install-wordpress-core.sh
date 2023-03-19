#!/bin/bash

WP_CONFIG_FILE_PATH="/var/www/html/wp-config.php"
# if config files exists; consider previous installation
if [ -f $WP_CONFIG_FILE_PATH ]; then
  wp db drop --yes
  cd /var/www/html/
  rm -rf ./*
fi

wp core download
wp config create --dbhost='mariadb' --dbname='wordpress' --dbuser='root' --dbpass='password'
wp db create
wp core install --url='127.0.0.1:12080' --title='Cebola testing' --admin_user='admin' --admin_email='admin@example.com' --admin_password='password'

#quick and dirty fix
chmod 777 /var/www/html -R

# keep alive hack - yes, yes, I know - it's a hack, but this IS Hackathon!!!
tail -f /dev/null
