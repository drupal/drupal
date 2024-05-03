# Drupal custom PHPStan rules

This directory contains PHPStan rules specifically developed for Drupal.

## Subdirectories

* _Rules_: contains the actual rules.
* _tests_: contains PHPUnit tests for the rules.
* _fixtures_: contains fixture files for the PHPUnit tests of the rules.

## Enabling rules

Rules are executed when they are added to the the phpstan.neon(.dist)
configuration file of a PHPStan scan run. You need to add them under the
`rules` entry in the file, specifying the fully qualified class name of the
rule. For example:
```

rules:
  - Drupal\PHPStan\Rules\ComponentTestDoesNotExtendCoreTest

```

## Testing rules

PHPStan rules must be tested in the context of the PHPStan testing framework,
that differs in terms of dependencies from Drupal's one.

Note that for this reason, these tests are run _separately_ from Drupal core
tests.

A _composer.json_ file is present in this directory, indicating the required
packages for the execution of the tests. Installing via composer
```
$ composer install
```
builds a _vendor_ subdirectory that includes all the packages required. Note
this packages' codebase is totally independent from Drupal core's one.

In the context of this directory, you can then execute the rule tests like
```
$ vendor/bin/phpunit tests
```
