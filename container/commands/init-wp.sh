#!/bin/bash

CEBOLA_DOT_USER_DIR="~/.cebola"
CEBOLA_PHP8_UPDATE_FILE="{$CEBOLA_DOT_USER_DIR}/php8-updated.lock"

function cebolaPrepare() {
  if [ ! -d "$CEBOLA_DOT_USER_DIR" ]; then
    mkdir -p "$CEBOLA_DOT_USER_DIR"
  fi
}

function cebolaInstallMissingPackages() {
  if [ -f "$CEBOLA_PHP8_UPDATE_FILE" ]; then
    echo "PHP8 lock file already exists. Skipping PHP8 upgrade."
    return
  fi

  apt-get install -qq -y wget nano
  apt-get install -qq -y lsb-release ca-certificates apt-transport-https software-properties-common gnupg2

  pecl -q install uopz

}

cebolaPrepare

apt-get -qq update && apt-get -qq -y upgrade

cebolaInstallMissingPackages

echo "Initialized!"

# keep alive hack - yes, yes, I know - it's a hack, but this IS Hackathon!!!
tail -f /dev/null
