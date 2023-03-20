![CEBOLA](https://cdn.indystack.com/validatethewp/2023/03/cropped-logo_transparent_background-1-768x177.png)
# CEBOLA security scanner

## ABOUT THE PROJECT

Designed to enhance your website’s security, WPHackCebola uses dynamic analysis to detect potential security vulnerabilities in WordPress installations running within containers.

Inspired by the wpgarlic proof-of-concept article, we’ve taken our approach further by simulating bogus requests and identifying where they produce unexpected output.

***

## HOW IT WORKS

With our dynamic tool, you can test your website against vulnerabilities that may exist within your plugins.

Our PHP command analyzes your plugin’s behavior and the data it accesses, including GET and POST parameters. We then inject our tool into your plugin’s cores, allowing us to intercept and retrieve data, send requests, and intercept function properties and return values. This helps us detect any unescaped output or leaked internal data, providing you with greater protection against potential security threats.

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
sudo apt-get upgrade -y
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

Run the cebola command
=======
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

Install other packages

[Linux]

Make sure that you also install these:

```
apt-get install php8.1-mysql
apt-get install php8.1-curl
```

### Install Docker

* Follow the instructions from the https://docs.docker.com/get-docker/

[Linux]

If you are running on another user the root, don't forget to add it to the docker permission group
https://docs.docker.com/engine/install/linux-postinstall/

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


Powered by

![CODABLE](https://cdn.indystack.com/validatethewp/2023/03/codeable-io-logo-vector.svg)
