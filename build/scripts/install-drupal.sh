#!/bin/sh

set -e

mysql -e 'create database drupal;'

pear channel-discover pear.drush.org
pear install drush/drush
phpenv rehash

cd ..
drush dl drupal --drupal-project-rename=drupal
cd drupal

drush site-install --db-url=mysql://root:@127.0.0.1/drupal --yes
echo "\$base_url = 'http://drupal.loc';" >> settings.php
export WEBROOT=`pwd`

mv ../wconsumer ./sites/all/modules/
drush en --yes wconsumer

cd sites/all/modules/wconsumer/