Mink Driver testsuite
=====================

This is the common testsuite for Mink drivers to ensure consistency among implementations.

Usage
-----

The testsuite of a driver should be based as follow:

```json
{
    "require": {
        "behat/mink": "~1.6@dev"
    },

    "autoload-dev": {
        "psr-4": {
            "Acme\\MyDriver\\Tests\\": "tests"
        }
    }
}
```

```xml
<!-- phpunit.xml.dist -->
<?xml version="1.0" encoding="UTF-8"?>

<phpunit colors="true" bootstrap="vendor/behat/mink/driver-testsuite/bootstrap.php">
    <php>
        <var name="driver_config_factory" value="Acme\MyDriver\Tests\Config::getInstance" />

        <server name="WEB_FIXTURES_HOST" value="http://test.mink.dev" />
    </php>

    <testsuites>
        <testsuite name="Functional tests">
            <directory>vendor/behat/mink/driver-testsuite/tests</directory>
        </testsuite>
        <!-- if needed to add more tests -->
        <testsuite name="Driver tests">
            <directory>./tests/</directory>
        </testsuite>
    </testsuites>

    <filter>
        <whitelist>
            <directory>./src</directory>
        </whitelist>
    </filter>
</phpunit>
```

Then create the driver config for the testsuite:

```php
// tests/Config.php

namespace Acme\MyDriver\Tests;

use Behat\Mink\Tests\Driver\AbstractConfig;

class Config extends AbstractConfig
{
    /**
     * Creates an instance of the config.
     *
     * This is the callable registered as a php variable in the phpunit.xml config file.
     * It could be outside the class but this is convenient.
     */
    public static function getInstance()
    {
        return new self();
    }

    /**
     * Creates driver instance.
     *
     * @return \Behat\Mink\Driver\DriverInterface
     */
    public function createDriver()
    {
        return new \Acme\MyDriver\MyDriver();
    }
}
```

Some other methods are available in the AbstractConfig which can be overwritten to adapt the testsuite to
the needs of the driver (skipping some tests for instance).

Adding Driver-specific Tests
----------------------------

When adding extra test cases specific to the driver, either use your own namespace or put them in the
``Behat\Mink\Tests\Driver\Custom`` subnamespace to ensure that you will not create conflicts with test cases
added in the driver testsuite in the future.
