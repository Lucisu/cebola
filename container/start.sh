composer install --working-dir=addons/mu-plugins/cebola

cd container
docker compose up -d
docker exec -it container-wpcore-1 chmod 777 /var/www/html -R

docker exec -it container-wpcore-1 /usr/local/bin/commands/install-missing-exts.sh
docker exec -it container-wpcli-1 /usr/local/bin/commands/install-wordpress-core.sh
