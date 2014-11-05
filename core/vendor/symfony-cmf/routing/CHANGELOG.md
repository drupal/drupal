Changelog
=========

* **2014-09-29**: ChainRouter does not require a RouterInterface, as a
  RequestMatcher and UrlGenerator is fine too. Fixed chain router interface to
  not force a RouterInterface.
* **2014-09-29**: Deprecated DynamicRouter::match in favor of matchRequest.

1.3.0-RC1
---------

* **2014-08-20**: Added an interface for the ChainRouter
* **2014-06-06**: Updated to PSR-4 autoloading

1.2.0
-----

Release 1.2.0

1.2.0-RC1
---------

* **2013-12-23**: add support for ChainRouter::getRouteCollection()
* **2013-01-07**: Removed the deprecated $parameters argument in
  RouteProviderInterface::getRouteByName and getRoutesByNames.

1.1.0
-----

Release 1.1.0

1.1.0-RC1
---------

* **2013-07-31**: DynamicRouter now accepts an EventDispatcher to trigger a
  RouteMatchEvent right before the matching starts
* **2013-07-29**: Renamed RouteAwareInterface to RouteReferrersReadInterface
  for naming consistency and added RouteReferrersInterface for write access.
* **2013-07-13**: NestedMatcher now expects a FinalMatcherInterface as second
  argument of the constructor

1.1.0-alpha1
------------

* **2013-04-30**: Dropped Symfony 2.1 support and got rid of
  ConfigurableUrlMatcher class
* **2013-04-05**: [ContentAwareGenerator] Fix locale handling to always respect
  locale but never have unnecessary ?locale=
