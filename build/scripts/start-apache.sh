#!/bin/sh

set -e

echo "Install apache"
sudo apt-get install -y --force-yes apache2 libapache2-mod-php5 php5-mysql php5-curl

www="$(dirname $TRAVIS_BUILD_DIR)/drupal"
echo "Setting webroot to $www"
sudo sed -i -e "s,/var/www,$www,g" /etc/apache2/sites-available/default

echo "Enable rewrite and actions modules"
sudo a2enmod rewrite
sudo a2enmod actions

echo "Add 'drupal.loc' to /etc/hosts"
sudo sh -c 'echo "127.0.0.1 drupal.loc" >> /etc/hosts'

echo "Restart apache"
sudo service apache2 restart