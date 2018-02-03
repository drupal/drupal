# Running tests

## Functional tests

* Run the functional tests:
  ```
  export SIMPLETEST_DB='mysql://root@localhost/dev_d8'
  export SIMPLETEST_BASE_URL='http://d8.dev'
  ./vendor/bin/phpunit -c core --testsuite functional
  ```

Note: functional tests have to be invoked with a user in the same group as the
web server user. You can either configure Apache (or nginx) to run as your own
system user or run tests as a privileged user instead.

To develop locally, a straightforward - but also less secure - approach is to
run tests as your own system user. To achieve that, change the default Apache
user to run as your system user. Typically, you'd need to modify
`/etc/apache2/envvars` on Linux or `/etc/apache2/httpd.conf` on Mac.

Example for Linux:

```
export APACHE_RUN_USER=<your-user>
export APACHE_RUN_GROUP=<your-group>
```

Example for Mac:

```
User <your-user>
Group <your-group>
```

## Functional javascript tests

Javascript tests use the Selenium2Driver which allows you to control a
big range of browsers. By default Drupal uses chromedriver to run tests.
For help installing and starting selenium, see http://mink.behat.org/en/latest/drivers/selenium2.html

* Make sure you have a recent version of chrome installed

* Install selenium-server-standalone and chromedriver

Example for Mac:

```
brew install selenium-server-standalone
brew install chromedriver
```

* Before running tests make sure that selenium-server is running
```
selenium-server -port 4444
```

* Set the correct driver args and run the tests:
```
export MINK_DRIVER_ARGS_WEBDRIVER='["chrome", null, "http://localhost:4444/wd/hub"]'
./vendor/bin/phpunit -c core --testsuite functional-javascript
```

* It is possible to use alternate browsers if the required dependencies are
installed. For example to use Firefox:

```
export MINK_DRIVER_ARGS_WEBDRIVER='["firefox", null, "http://localhost:4444/wd/hub"]'
./vendor/bin/phpunit -c core --testsuite functional-javascript
```

* To force all BrowserTestBase (including legacy JavascriptTestBase) tests to use
webdriver:

```
export MINK_DRIVER_CLASS='Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver'
./vendor/bin/phpunit -c core --testsuite functional-javascript
```

## Running legacy javascript tests

Older javascript test may use the PhantomJSDriver. To run these tests you will
have to install and start PhantomJS.

* Start PhantomJS:
  ```
  phantomjs --ssl-protocol=any --ignore-ssl-errors=true ./vendor/jcalderonzumba/gastonjs/src/Client/main.js 8510 1024 768 2>&1 >> /dev/null &
  ```

* Then you can run the test:
```
./vendor/bin/phpunit -c core --testsuite functional-javascript
```

## Running tests with a different user

If the default user is e.g. `www-data`, the above functional tests will have to
be invoked with sudo instead:

```
export SIMPLETEST_DB='mysql://root@localhost/dev_d8'
export SIMPLETEST_BASE_URL='http://d8.dev'
sudo -u www-data -E ./vendor/bin/phpunit -c core --testsuite functional
sudo -u www-data -E ./vendor/bin/phpunit -c core --testsuite functional-javascript
```
