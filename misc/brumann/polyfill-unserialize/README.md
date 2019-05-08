Polyfill unserialize [![Build Status](https://travis-ci.org/dbrumann/polyfill-unserialize.svg?branch=master)](https://travis-ci.org/dbrumann/polyfill-unserialize)
===

Backports unserialize options introduced in PHP 7.0 to older PHP versions.
This was originally designed as a Proof of Concept for Symfony Issue [#21090](https://github.com/symfony/symfony/pull/21090).

You can use this package in projects that rely on PHP versions older than PHP 7.0.
In case you are using PHP 7.0+ the original `unserialize()` will be used instead.

From the [documentation](https://secure.php.net/manual/en/function.unserialize.php):

> Warning: Do not pass untrusted user input to unserialize(). Unserialization can
> result in code being loaded and executed due to object instantiation
> and autoloading, and a malicious user may be able to exploit this.

This warning holds true even when `allowed_classes` is used.

Requirements
------------

 - PHP 5.3+

Installation
------------

You can install this package via composer:

```
composer require brumann/polyfill-unserialize "^1.0"
```

Known Issues
------------

There is a mismatch in behavior when `allowed_classes` in `$options` is not
of the correct type (array or boolean). PHP 7.1 will issue a warning, whereas
PHP 7.0 will not. I opted to copy the behavior of the former.

Tests
-----

You can run the test suite using PHPUnit. It is intentionally not bundled as
dev dependency to make sure this package has the lowest restrictions on the
implementing system as possible.

Please read the [PHPUnit Manual](https://phpunit.de/manual/current/en/installation.html)
for information how to install it on your system.

You can run the test suite as follows:

```
phpunit -c phpunit.xml.dist tests/
```

Contributing
------------

This package is considered feature complete. As such I will likely not update it
unless there are security issues.

Should you find any bugs or have questions, feel free to submit an Issue or a Pull Request.
