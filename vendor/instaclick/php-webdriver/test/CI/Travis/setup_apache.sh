#!/bin/sh

# set up Apache
# @see https://github.com/travis-ci/travis-ci.github.com/blob/master/docs/user/languages/php.md#apache--php

sudo a2enmod rewrite actions fastcgi alias ssl

# configure apache root dir
sudo sed -i -e "s,/var/www,$(pwd),g" /etc/apache2/sites-available/default
sudo service apache2 restart
