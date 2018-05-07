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

## Nightwatch tests

- Ensure your vendor directory is populated (e.g. by running `composer install`)
- If you're running PHP 7.0 or greater you will need to upgrade PHPUnit with `composer run-script drupal-phpunit-upgrade`
- Install [Node.js](https://nodejs.org/en/download/) and [yarn](https://yarnpkg.com/en/docs/install). The versions required are specificed inside core/package.json in the `engines` field
- Install [Google Chrome](https://www.google.com/chrome/browser/desktop/index.html)
- Inside the `core` folder, run `yarn install`
- Configure the nightwatch settings by copying `.env.example` to `.env` and editing as necessary.
- Ensure you have a web server running (as instructed in `.env`)
- Again inside the `core` folder, run `yarn test:nightwatch` to run the tests. By default this will output reports to `core/reports`
- Nightwatch will run tests for core, as well as contrib and custom modules and themes. It will search for tests located under folders with the pattern `**/tests/**/Nightwatch/(Tests|Commands|Assertions)`
- To run only core tests, run `yarn test:nightwatch --tag core`
- To skip running core tests, run `yarn test:nightwatch --skiptags core`
- To run a single test, run e.g. `yarn test:nightwatch tests/Drupal/Nightwatch/Tests/exampleTest.js`

Nightwatch tests can be placed in any folder with the pattern `**/tests/**/Nightwatch/(Tests|Commands|Assertions)`. For example:
```
tests/Nightwatch/Tests
src/tests/Nightwatch/Tests
tests/src/Nightwatch/Tests
tests/Nightwatch/Commands
```

It's helpful to follow existing patterns for test placement, so for the action module they would go in `core/modules/action/tests/src/Nightwatch`.
The Nightwatch configuration, as well as global tests, commands, and assertions which span many modules/systems, are located in `core/tests/Drupal/Nightwatch`.

If your core directory is located in a subfolder (e.g. `docroot`), then you can edit the search directory in `.env` to pick up tests outside of your Drupal directory.
Tests outside of the `core` folder will run in the version of node you have installed. If you want to transpile with babel (e.g. to use `import` statements) outside of core,
then add your own babel config to the root of your project. For example, if core is located under `docroot/core`, then you could run `yarn add babel-preset-env` inside
`docroot`, then copy the babel settings from `docroot/core/package.json` into `docroot/package.json`.
