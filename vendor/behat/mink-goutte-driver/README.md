Mink Goutte Driver
==================

[![Latest Stable Version](https://poser.pugx.org/behat/mink-goutte-driver/v/stable.svg)](https://packagist.org/packages/behat/mink-goutte-driver)
[![Latest Unstable Version](https://poser.pugx.org/behat/mink-goutte-driver/v/unstable.svg)](https://packagist.org/packages/behat/mink-goutte-driver)
[![Total Downloads](https://poser.pugx.org/behat/mink-goutte-driver/downloads.svg)](https://packagist.org/packages/behat/mink-goutte-driver)
[![Build Status](https://travis-ci.org/minkphp/MinkGoutteDriver.svg?branch=master)](https://travis-ci.org/minkphp/MinkGoutteDriver)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/minkphp/MinkGoutteDriver/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/minkphp/MinkGoutteDriver/)
[![Code Coverage](https://scrutinizer-ci.com/g/minkphp/MinkGoutteDriver/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/minkphp/MinkGoutteDriver/)
[![License](https://poser.pugx.org/behat/mink-goutte-driver/license.svg)](https://packagist.org/packages/behat/mink-goutte-driver)

Usage Example
-------------

``` php
<?php

require "vendor/autoload.php";

use Behat\Mink\Mink,
    Behat\Mink\Session,
    Behat\Mink\Driver\GoutteDriver,
    Behat\Mink\Driver\Goutte\Client as GoutteClient;

$mink = new Mink(array(
    'goutte' => new Session(new GoutteDriver(new GoutteClient())),
));

$session = $mink->getSession('goutte');
$session->visit("http://php.net/");
$session->getPage()->clickLink('Downloads');
echo $session->getCurrentUrl() . PHP_EOL;
```

Installation
------------

Add a file composer.json with content:

``` json
{
    "require": {
        "behat/mink":               "~1.5",
        "behat/mink-goutte-driver": "~1.0"
    }
}
```

(or merge the above into your project's existing composer.json file)

``` bash
$> curl -sS https://getcomposer.org/installer | php
$> php composer.phar install
```

Maintainers
-----------

* Christophe Coevoet [stof](https://github.com/stof)
* Other [awesome developers](https://github.com/minkphp/MinkGoutteDriver/graphs/contributors)
