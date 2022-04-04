#!/bin/bash

# Config
PROJECT_DIR=/home/pfadifot/www/rondo
CONFIG_FILE=/home/pfadifot/_config.rondo.php
HTACCESS_FILE=/home/pfadifot/.htaccess_rondo

echo -e "Working with Directory $PROJECT_DIR\n"

# Check if directory exists and is a git repository
if [ ! -d "$PROJECT_DIR/.git" ]; then
    # Clone fresh repo if not yet a git repository
    rm -rf $PROJECT_DIR
    mkdir $PROJECT_DIR
    cd $PROJECT_DIR
    git clone --recursive https://github.com/rondo-verlag/song-manager.git .
else
    # Update Source Code
    cd $PROJECT_DIR
    git status
    git pull --rebase
    git submodule update --recursive
fi

# Install Config
cp $CONFIG_FILE $PROJECT_DIR/src/api/_conf.php
cp $HTACCESS_FILE $PROJECT_DIR/src/.htaccess

# Composer Install
curl -sS https://getcomposer.org/installer | php81
php81 composer.phar install


echo -e "\n ======= Installation Done ========\n"
