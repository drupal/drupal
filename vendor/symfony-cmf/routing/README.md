# Symfony CMF Routing Component

[![Build Status](https://travis-ci.org/symfony-cmf/routing.svg?branch=master)](https://travis-ci.org/symfony-cmf/routing)
[![Latest Stable Version](https://poser.pugx.org/symfony-cmf/routing/version.png)](https://packagist.org/packages/symfony-cmf/routing)
[![Total Downloads](https://poser.pugx.org/symfony-cmf/routing/d/total.png)](https://packagist.org/packages/symfony-cmf/routing)

The Symfony CMF Routing component extends the Symfony2 core routing component.
It provides:

 * A ChainRouter to run several routers in parallel
 * A DynamicRouter that can load routes from any database and can generate
   additional information in the route match.

Even though it has Symfony in its name, the Routing component does not need the
full Symfony2 Framework and can be used in standalone projects.

For Symfony 2 projects, an optional
[RoutingBundle](https://github.com/symfony-cmf/RoutingBundle)
is also available.

This library is provided by the [Symfony Content Management Framework (CMF) project](http://cmf.symfony.com/)
and licensed under the [MIT License](LICENSE).


## Requirements

* The Symfony Routing component (>= 2.2.0)
* See also the `require` section of [composer.json](composer.json)


## Documentation

For the install guide and reference, see:

* [Routing component documentation](http://symfony.com/doc/master/cmf/components/routing/index.html)

See also:

* [All Symfony CMF documentation](http://symfony.com/doc/master/cmf/index.html) - complete Symfony CMF reference
* [Symfony CMF Website](http://cmf.symfony.com/) - introduction, live demo, support and community links


## Contributing

Pull requests are welcome. Please see our
[CONTRIBUTING](https://github.com/symfony-cmf/symfony-cmf/blob/master/CONTRIBUTING.md)
guide.

Unit and/or functional tests exist for this component. See the
[Testing documentation](http://symfony.com/doc/master/cmf/components/testing.html)
for a guide to running the tests.

Thanks to
[everyone who has contributed](https://github.com/symfony-cmf/Routing/contributors) already.
