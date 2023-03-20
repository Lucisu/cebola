composer install --working-dir=../addons/mu-plugins/cebola
cd container
docker compose up -d
docker exec -it container-wp-1 chmod 777 /var/www/html -R
