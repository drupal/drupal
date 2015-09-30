Mink BrowserKit Driver
======================

[![Latest Stable Version](https://poser.pugx.org/behat/mink-browserkit-driver/v/stable.png)](https://packagist.org/packages/behat/mink-browserkit-driver)
[![Latest Unstable Version](https://poser.pugx.org/behat/mink-browserkit-driver/v/unstable.svg)](https://packagist.org/packages/behat/mink-browserkit-driver)
[![Total Downloads](https://poser.pugx.org/behat/mink-browserkit-driver/downloads.png)](https://packagist.org/packages/behat/mink-browserkit-driver)
[![Build Status](https://travis-ci.org/Behat/MinkBrowserKitDriver.svg?branch=master)](https://travis-ci.org/Behat/MinkBrowserKitDriver)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/Behat/MinkBrowserKitDriver/badges/quality-score.png?s=0443d284940e099ea560eb39b6b2fcdc5d4e7f29)](https://scrutinizer-ci.com/g/Behat/MinkBrowserKitDriver/)
[![Code Coverage](https://scrutinizer-ci.com/g/Behat/MinkBrowserKitDriver/badges/coverage.png?s=48960c4495488ab0b7d310b62322f017497f5bfa)](https://scrutinizer-ci.com/g/Behat/MinkBrowserKitDriver/)
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

* Konstantin Kudryashov [everzet](http://github.com/everzet)
* Other [awesome developers](https://github.com/Behat/MinkBrowserKitDriver/graphs/contributors)
