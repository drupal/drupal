1.2.1 / 2016-03-05
==================

Testsuite:

* Added testing on PHP 7

1.2.0 / 2015-09-21
==================

New features:

* Added support for Goutte 3.1+

Misc:

* Updated the repository structure to PSR-4

1.1.0 / 2014-10-09
==================

The driver now relies on BrowserKitDriver 1.2.x, so all changes of this driver are relevant.
The changes below only describe the changes related to GoutteDriver specifically.

New features:

* Added the possibility to use a normal Goutte client instead of requiring to use an extended one
* Added the support of Goutte 2.0 as well

Bug fixes:

* Fixed the support of disabling basic auth
* Fixed the resetting of the driver to reset the basic auth

Testing:

* Updated the testsuite to use the new Mink 1.6 driver testsuite
* Added testing on HHVM
