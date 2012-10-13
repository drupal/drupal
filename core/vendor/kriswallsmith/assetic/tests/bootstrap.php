<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!$loader = @include __DIR__.'/../vendor/autoload.php') {
    die('You must set up the project dependencies, run the following commands:'.PHP_EOL.
        'curl -s http://getcomposer.org/installer | php'.PHP_EOL.
        'php composer.phar install'.PHP_EOL);
}

$loader->add('Assetic\Test', __DIR__);

if (isset($_SERVER['TWIG_LIB'])) {
    $loader->add('Twig_', $_SERVER['TWIG_LIB']);
}

if (isset($_SERVER['LESSPHP'])) {
    require_once $_SERVER['LESSPHP'];
}

if (isset($_SERVER['CSSMIN'])) {
    require_once $_SERVER['CSSMIN'];
}

if (isset($_SERVER['JSMIN'])) {
    require_once $_SERVER['JSMIN'];
}

if (isset($_SERVER['JSMINPLUS'])) {
    require_once $_SERVER['JSMINPLUS'];
}

if (isset($_SERVER['PACKAGER'])) {
    require_once $_SERVER['PACKAGER'];
}

if (isset($_SERVER['PACKER'])) {
    require_once $_SERVER['PACKER'];
}
