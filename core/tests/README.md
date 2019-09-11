# Running tests

## Setting up

### PHP dependencies

You need the Drupal core development dependencies installed, in order to run
any tests. You can install them using Composer by running
```
composer install
```
in the Drupal root directory. These dependencies should not be installed on a
production site.

### Test directory

Create a directory called sites/simpletest and make sure that it is writable by
the web server and/or all users.

### PHPUnit configuration

Copy the core/phpunit.xml.dist file to phpunit.xml, and place it somewhere
convenient (inside the core directory may not be the best spot, since that
directory may be managed by Composer or Git). You can use the -c option on the
command line to tell PHPUnit where this file is (use the full path).

Settings to change in this file:
* SIMPLETEST_BASE_URL: The URL of your site
* SIMPLETEST_DB: The URL of your Drupal database
* The bootstrap attribute of the top-level phpunit tag, to take into account
  the location of the file
* BROWSERTEST_OUTPUT_DIRECTORY: Set to sites/simpletest/browser_output;
  you will also want to uncomment the printerClass attribute of the
  top-level phpunit tag.

### Additional setup for JavaScript tests

To run JavaScript tests  based on the
\Drupal\FunctionalJavascriptTests\WebDriverTestBase base class, you will need
to install the following additional software:

* Google Chrome or Chromium browser
* chromedriver (tested with version 2.45) -- see
  https://sites.google.com/a/chromium.org/chromedriver/
* PHP 7.1 or higher

## Running unit, functional, and kernel tests

The PHPUnit executable is vendor/bin/phpunit -- you will need to locate your
vendor directory (which may be outside the Drupal root).

Here are commands to run one test class, list groups, and run all the tests in
a particular group:
```
./vendor/bin/phpunit -c /path/to/your/phpunit.xml path/to/your/class/file.php
./vendor/bin/phpunit --list-groups
./vendor/bin/phpunit -c /path/to/your/phpunit.xml --group Groupname
```

More information on running tests can be found at
https://www.drupal.org/docs/8/phpunit/running-phpunit-tests

## Running Functional JavaScript tests

You can run JavaScript tests that are based on the
\Drupal\FunctionalJavascriptTests\WebDriverTestBase base class in the same way
as other PHPUnit tests, except that before you start, you will need to start
chromedriver using port 4444, and keep it running:
```
/path/to/chromedriver --port=4444
```

## Running Nightwatch tests

* Ensure your vendor directory is populated
  (e.g. by running `composer install`)
* If you're running PHP 7.0 or greater you will need to upgrade PHPUnit with
  `composer run-script drupal-phpunit-upgrade`
* Install [Node.js](https://nodejs.org/en/download/) and
  [yarn](https://yarnpkg.com/en/docs/install). The versions required are
  specified inside core/package.json in the `engines` field. You can use
  [nvm](https://github.com/nvm-sh/nvm) and [yvm](https://github.com/tophat/yvm)
  to manage your local versions of these.
* Install
  [Google Chrome](https://www.google.com/chrome/browser/desktop/index.html)
* Inside the `core` folder, run `yarn install`
* Configure the nightwatch settings by copying `.env.example` to `.env` and
  editing as necessary.
* Ensure you have a web server running (as instructed in `.env`)
* Again inside the `core` folder, run `yarn test:nightwatch --env local` to run
  the tests.
  By default this will output reports to `core/reports`
* Nightwatch will run tests for core, as well as contrib and custom modules and
  themes. It will search for tests located under folders with the pattern
  `**/tests/**/Nightwatch/(Tests|Commands|Assertions)`
* To run only core tests, run `yarn test:nightwatch --tag core`
* To skip running core tests, run `yarn test:nightwatch --skiptags core`
* To run a single test, run e.g.
  `yarn test:nightwatch tests/Drupal/Nightwatch/Tests/exampleTest.js`

Nightwatch tests, as well as custom commands, assertions and pages, can be
placed in any folder with the pattern
`**/tests/**/Nightwatch/(Tests|Commands|Assertions|Pages)`. For example:
```
tests/Nightwatch/Tests
src/tests/Nightwatch/Tests
tests/src/Nightwatch/Tests
tests/Nightwatch/Commands
tests/src/Nightwatch/Assertions
tests/src/Nightwatch/Pages
```

It's helpful to follow existing patterns for test placement, so for the action
module they would go in `core/modules/action/tests/src/Nightwatch`.
The Nightwatch configuration, as well as global tests, commands, and assertions
which span many modules/systems, are located in `core/tests/Drupal/Nightwatch`.

If your core directory is located in a subfolder (e.g. `docroot`), then you can
edit the search directory in `.env` to pick up tests outside of your Drupal
directory. Tests outside of the `core` folder will run in the version of node
you have installed. If you want to transpile with babel (e.g. to use `import`
statements) outside of core, then add your own babel config to the root of your
project. For example, if core is located under `docroot/core`, then you could
run `yarn add babel-preset-env` inside `docroot`, then copy the babel settings
from `docroot/core/package.json` into `docroot/package.json`.

## Troubleshooting test running

If you run into file permission problems while running tests, you may need to
invoke the phpunit executable with a user in the same group as the web server
user, or with access to files owned by the web server user. For example:
```
sudo -u www-data ./vendor/bin/phpunit -c /path/to/your/phpunit.xml --group Groupname
```

If you have permission problems accessing files after running tests, try
putting
```
$settings['file_chmod_directory'] = 02775;
```
in your settings.php or local.settings.php file.

You may need to use absolute paths in your phpunit.xml file, and/or in your
phpunit command arguments.
