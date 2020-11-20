Mink BrowserKit Driver
======================

[![Latest Stable Version](https://poser.pugx.org/behat/mink-browserkit-driver/v/stable.png)](https://packagist.org/packages/behat/mink-browserkit-driver)
[![Latest Unstable Version](https://poser.pugx.org/behat/mink-browserkit-driver/v/unstable.svg)](https://packagist.org/packages/behat/mink-browserkit-driver)
[![Total Downloads](https://poser.pugx.org/behat/mink-browserkit-driver/downloads.png)](https://packagist.org/packages/behat/mink-browserkit-driver)
[![Build Status](https://travis-ci.org/minkphp/MinkBrowserKitDriver.svg?branch=master)](https://travis-ci.org/minkphp/MinkBrowserKitDriver)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/minkphp/MinkBrowserKitDriver/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/minkphp/MinkBrowserKitDriver/)
[![Code Coverage](https://scrutinizer-ci.com/g/minkphp/MinkBrowserKitDriver/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/minkphp/MinkBrowserKitDriver/)
[![License](https://poser.pugx.org/behat/mink-browserkit-driver/license.svg)](https://packagist.org/packages/behat/mink-browserkit-driver)

Usage Example
-------------

``` php
<?php

use Behat\Mink\Mink,
    Behat\Mink\Session,
    Behat\Mink\Driver\BrowserKitDriver;

use Symfony\Component\HttpKernel\Client;

$app  = require_once(__DIR__.'/app.php'); // Silex app

$mink = new Mink(array(
    'silex' => new Session(new BrowserKitDriver(new Client($app))),
));

$mink->getSession('silex')->getPage()->findLink('Chat')->click();
```

Installation
------------

``` json
{
    "require": {
        "behat/mink":                   "~1.5",
        "behat/mink-browserkit-driver": "~1.1"
    }
}
```

``` bash
$> curl -sS https://getcomposer.org/installer | php
$> php composer.phar install
```

Maintainers
-----------

* Christophe Coevoet [stof](https://github.com/stof)
* Other [awesome developers](https://github.com/minkphp/MinkBrowserKitDriver/graphs/contributors)
