#!/bin/sh

set -e

sudo apt-get install -y --force-yes apache2 libapache2-mod-php5 php5-mysql php5-curl

www="$(dirname $TRAVIS_BUILD_DIR)/drupal"
sudo sed -i -e "s,/var/www,$www,g" /etc/apache2/sites-available/default
sudo a2enmod rewrite
sudo a2enmod actions

sudo sh -c 'echo "127.0.0.1 drupal.loc" >> /etc/hosts'

sudo service apache2 restart