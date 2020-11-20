#!/bin/sh

# set up Selenium for functional tests

wget --max-redirect=1 https://goo.gl/s4o9Vx -O selenium.jar

java -jar selenium.jar &
