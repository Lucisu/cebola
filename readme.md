Installation
============

[Mac/Linux]

[Windows]


Installations:
--------------
There are two ways to install it on your Windows machine
*  Obtaining the installation (.exe) files
*  Use the Chocolatey installs the stuff you need that don't come with Windows by default.
   NOTE: Chocolatey is similar to a Homebrew(Mac).

#####  Obtaining the installation (.exe) files
1. Php  https://www.php.net/downloads.php
2. Composer https://getcomposer.org/download/
3. Docker dektop for Windows https://docs.docker.com/desktop/install/windows-install/

#####  Use Chocolatey
###### Setup Chocolatey https://docs.chocolatey.org/en-us/choco/setup

1. Install PHP
   > `>` choco install php

Note: you may get the error that some packages are missing. In that case please find your .ini file

3. Install Composer
> `>` choco install composer

When you are done with installations you should open  your Docker client, and you can continue there.

```bash
cd ./includes/cebola
composer install

cd ./container/
docker compose up
```

You can access the site on http://localhost:8000/
You can login with:
- user: `admin`
- password: `secret`

Run the cebola command
======================