# Contributing

If you would like to help, please take a look at the list of
[issues](https://github.com/composer/installers/issues).

## Pull requests

* [Fork and clone](https://help.github.com/articles/fork-a-repo).
* Run the command `php composer.phar install` to install the dependencies.
  This will also install the dev dependencies. See [Composer](https://getcomposer.org/doc/03-cli.md#install).
* Use the command `phpunit` to run the tests. See [PHPUnit](http://phpunit.de).
* Create a branch, commit, push and send us a
  [pull request](https://help.github.com/articles/using-pull-requests).

To ensure a consistent code base, you should make sure the code follows the
coding standards [PSR-1](http://www.php-fig.org/psr/psr-1/) and 
[PSR-2](http://www.php-fig.org/psr/psr-2/).

### Create a new Installer

* Create class extends `Composer\Installers\BaseInstaller` with your Installer.
* Create unit tests as a separate class or as part of a `Composer\Installers\Test\InstallerTest`.
* Add information about your Installer in `README.md` in section "Current Supported Package Types".
* Run the tests.
