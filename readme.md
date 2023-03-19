# CEBOLA security scanner

================================================

## ABOUT THE PROJECT

We help you find vulnerability in your Wordpress plugins and themes.

***

## HOW IT WORKS

By running the **Cebola scanner**, we are checking your plugins and themes provided in the command line and showing you
some well known vulnerability nested there.
We do that by creating a clean Wordpress instance, with the given plugin and adding our security expertise to get the
week points and issues from it.
We do that by using Docker containers to have up the full needed infrastructure and applying our scripts

##### Obtaining the installation (.exe) files

1. Php  https://www.php.net/downloads.php
2. Composer https://getcomposer.org/download/
3. Docker dektop for Windows https://docs.docker.com/desktop/install/windows-install/

##### Use Chocolatey

###### Setup Chocolatey https://docs.chocolatey.org/en-us/choco/setup

1. Install PHP
``` 
choco install php
```

Note: you may get the error that some packages are missing. In that case please find your .ini file

By running the **Cebola scanner**, we are checking your plugins and themes provided in the command line and showing you
some well known vulnerability nested there.
We do that by creating a clean Wordpress instance, with the given plugin and adding our security expertise to get the
week points and issues from it.
We do that by using Docker containers to have up the full needed infrastructure and applying our scripts

***

## START RUNNING THE SCANNER

[Windows]

#### There are two ways to install it on your Windows machine

* Obtaining the installation (.exe) files
* Use the Chocolatey installs the stuff you need that don't come with Windows by default.
  NOTE: Chocolatey is similar to a Homebrew(Mac). https://docs.chocolatey.org/en-us/choco/setup

[Mac]

#### The recommended way to install the packages on Mac is by using Homebrew. You can do that by following the instructions at https://brew.sh/

[Linux]

```
sudo apt-get update 
sudo apt-get upgrade
```

####   

### Install PHP

[Windows]

* Using the installation (.exe) files -> https://www.php.net/downloads.php

* Chocolatey Install
```
choco install php
```

[Mac]

* Using homebrew run
```
brew install php
```

[Linux]

* Run the apt-get command
```
sudo apt-get install php
```

### Install Compose

[Windows]

* Using the installation (.exe) files -> https://getcomposer.org/download/

* Chocolatey Install
```
choco install composer
```

When you are done with installations you should open your Docker client, and you can continue there.

[Mac]

* Using homebrew run
```
brew install composer
```

[Linux]

```
cd ~
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
sudo php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
```

### Install Docker

* Follow the instructions from the https://docs.docker.com/get-docker/

### Run the infrastructure

* Clone the repo
* Make you place yourself in the root dir of the project
* Run the scanning command
  > ./cebola --plugin *plugin-slug*

  ** This plugin can be the slug as writen on [wordpress.org](https://wordpress.org/) or it can be a url to a zip file.
  Local zip files can ony work if you provide a valid path within the container.

## SEE THE RESULTS

You can access the site on http://localhost:12080/
You can login with:

- user: `admin`
- password: `secret`
