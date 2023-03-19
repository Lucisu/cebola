#!/bin/bash

CEBOLA_DOT_USER_DIR="/root/.cebola"
CEBOLA_PHP8_UPDATE_FILE="{$CEBOLA_DOT_USER_DIR}/php8-updated.lock"

function cebolaPrepare() {
  if [ ! -d $CEBOLA_DOT_USER_DIR ]; then
    mkdir $CEBOLA_DOT_USER_DIR
  fi
}

function cebolaInstallMissingLibraries() {
  apt-get install -qq -y wget nano
  apt-get install -qq -y lsb-release ca-certificates apt-transport-https software-properties-common gnupg2
}

function cebolaInstallUopz() {
  apt-get update
  pecl install xdebug
  docker-php-ext-enable xdebug
  pecl install uopz
  /etc/init.d/apache2 reload
}

apt-get -qq update && apt-get -qq -y upgrade
cebolaInstallMissingLibraries
cebolaInstallUopz

