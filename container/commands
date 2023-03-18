#!/usr/bin/env bash
php -c /etc/php/8.0/apache2/php.ini cebola -v 3 --plugin woocommerce --fresh

docker ps

docker exec -it container-wp-1 /bin/bash
echo '<?php phpinfo();' > abc.php


apt-get update && pecl install xdebug && docker-php-ext-enable xdebug && pecl install uopz && /etc/init.d/apache2 reload

extension=uopz.so

sudo chmod a+rwx -R FOLDER

docker compose down && docker system prune -f && docker volume prune -f

docker container stop $(docker container ls -aq)
docker container rm $(docker container ls -aq)
docker rmi $(docker images -aq)