Installation
============

```bash
composer install --working-dir=addons/mu-plugins/cebola

cd ./container/
docker compose up
```

You can access the site on http://localhost:8000/
You can login with:
 - user: `admin`
 - password: `secret`

Run the cebola command
======================

```bash
./cebola --plugin plugin-slug
```
This plugin can be the slug as writen on wordpress.org
It can be a url to a zip file.
Local zip files can ony work if you provide a valid path within the container.
