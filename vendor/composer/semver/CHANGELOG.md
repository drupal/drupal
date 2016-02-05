# Change Log

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

### [1.0.0] 2015-09-21

  * Break: `VersionConstraint` renamed to `Constraint`.
  * Break: `SpecificConstraint` renamed to `AbstractConstraint`.
  * Break: `LinkConstraintInterface` renamed to `ConstraintInterface`.
  * Break: `VersionParser::parseNameVersionPairs` was removed.
  * Changed: `VersionParser::parseConstraints` allows (but ignores) build metadata now.
  * Changed: `VersionParser::parseConstraints` allows (but ignores) prefixing numeric versions with a 'v' now.
  * Changed: Fixed namespace(s) of test files.
  * Changed: `Comparator::compare` no longer throws `InvalidArgumentException`.
  * Changed: `VersionConstraint` now throws `InvalidArgumentException`.

### [0.1.0] 2015-07-23

  * Added: `Composer\Semver\Comparator`, various methods to compare versions.
  * Added: various documents such as README.md, LICENSE, etc.
  * Added: configuration files for Git, Travis, php-cs-fixer, phpunit.
  * Break: the following namespaces were renamed:
    - Namespace: `Composer\Package\Version` -> `Composer\Semver`
    - Namespace: `Composer\Package\LinkConstraint` -> `Composer\Semver\Constraint`
    - Namespace: `Composer\Test\Package\Version` -> `Composer\Test\Semver`
    - Namespace: `Composer\Test\Package\LinkConstraint` -> `Composer\Test\Semver\Constraint`
  * Changed: code style using php-cs-fixer.
