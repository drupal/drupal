Mink Goutte Driver
==================

[![Latest Stable Version](https://poser.pugx.org/behat/mink-goutte-driver/v/stable.svg)](https://packagist.org/packages/behat/mink-goutte-driver)
[![Latest Unstable Version](https://poser.pugx.org/behat/mink-goutte-driver/v/unstable.svg)](https://packagist.org/packages/behat/mink-goutte-driver)
[![Total Downloads](https://poser.pugx.org/behat/mink-goutte-driver/downloads.svg)](https://packagist.org/packages/behat/mink-goutte-driver)
[![Build Status](https://travis-ci.org/Behat/MinkGoutteDriver.svg?branch=master)](https://travis-ci.org/Behat/MinkGoutteDriver)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/Behat/MinkGoutteDriver/badges/quality-score.png?s=ca141bb2cad18e74cf3d3b132b1a6aa0f3f004a5)](https://scrutinizer-ci.com/g/Behat/MinkGoutteDriver/)
[![Code Coverage](https://scrutinizer-ci.com/g/Behat/MinkGoutteDriver/badges/coverage.png?s=ca2d17a948660bfaeb4a95bf1a709644305c54f3)](https://scrutinizer-ci.com/g/Behat/MinkGoutteDriver/)
[![License](https://poser.pugx.org/behat/mink-goutte-driver/license.svg)](https://packagist.org/packages/behat/mink-goutte-driver)

Usage Example
-------------

``` php
<?php

use Behat\Mink\Mink,
    Behat\Mink\Session,
    Behat\Mink\Driver\GoutteDriver,
    Behat\Mink\Driver\Goutte\Client as GoutteClient;

$startUrl = 'http://example.com';

$mink = new Mink(array(
    'goutte' => new Session(new GoutteDriver(new GoutteClient($startUrl))),
));

$mink->getSession('goutte')->getPage()->findLink('Chat')->click();
```

Installation
------------

``` json
{
    "require": {
        "behat/mink":               "~1.5",
        "behat/mink-goutte-driver": "~1.0"
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
* Other [awesome developers](https://github.com/Behat/MinkGoutteDriver/graphs/contributors)
