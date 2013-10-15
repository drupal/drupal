<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2013 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Symfony\Cmf\Component\Routing\Tests\NestedMatcher;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Cmf\Component\Routing\NestedMatcher\UrlMatcher;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;

use Symfony\Cmf\Component\Routing\Test\CmfUnitTestCase;

class UrlMatcherTest extends CmfUnitTestCase
{
    protected $routeDocument;
    protected $routeCompiled;
    protected $matcher;
    protected $context;
    protected $request;

    protected $url = '/foo/bar';

    public function setUp()
    {
        $this->routeDocument = $this->buildMock('Symfony\Cmf\Component\Routing\Tests\Routing\RouteMock', array('getDefaults', 'getRouteKey', 'compile'));
        $this->routeCompiled = $this->buildMock('Symfony\Component\Routing\CompiledRoute');

        $this->context = $this->buildMock('Symfony\Component\Routing\RequestContext');
        $this->request = Request::create($this->url);

        $this->matcher = new UrlMatcher(new RouteCollection(), $this->context);
    }

    public function testMatchRouteKey()
    {
        $this->doTestMatchRouteKey($this->url);
    }

    public function testMatchNoKey()
    {
        $this->doTestMatchRouteKey(null);
    }

    public function doTestMatchRouteKey($routeKey)
    {
        $this->routeCompiled->expects($this->atLeastOnce())
            ->method('getStaticPrefix')
            ->will($this->returnValue($this->url))
        ;
        $this->routeCompiled->expects($this->atLeastOnce())
            ->method('getRegex')
            ->will($this->returnValue('#'.str_replace('/', '\/', $this->url).'#'))
        ;
        $this->routeDocument->expects($this->atLeastOnce())
            ->method('compile')
            ->will($this->returnValue($this->routeCompiled))
        ;
        $this->routeDocument->expects($this->atLeastOnce())
            ->method('getRouteKey')
            ->will($this->returnValue($routeKey))
        ;
        $this->routeDocument->expects($this->atLeastOnce())
            ->method('getDefaults')
            ->will($this->returnValue(array('foo' => 'bar')))
        ;

        $mockCompiled = $this->buildMock('Symfony\Component\Routing\CompiledRoute');
        $mockCompiled->expects($this->any())
            ->method('getStaticPrefix')
            ->will($this->returnValue('/no/match'))
        ;
        $mockRoute = $this->getMockBuilder('Symfony\Component\Routing\Route')->disableOriginalConstructor()->getMock();
        $mockRoute->expects($this->any())
            ->method('compile')
            ->will($this->returnValue($mockCompiled))
        ;
        $routeCollection = new RouteCollection();
        $routeCollection->add('some', $mockRoute);
        $routeCollection->add('_company_more', $this->routeDocument);
        $routeCollection->add('other', $mockRoute);

        $results = $this->matcher->finalMatch($routeCollection, $this->request);

        $expected = array(
            RouteObjectInterface::ROUTE_NAME => ($routeKey) ? $routeKey : '_company_more',
            RouteObjectInterface::ROUTE_OBJECT => $this->routeDocument,
            'foo' => 'bar',
        );

        $this->assertEquals($expected, $results);
    }

    public function testMatchNoRouteObject()
    {
        $this->routeCompiled->expects($this->atLeastOnce())
            ->method('getStaticPrefix')
            ->will($this->returnValue($this->url))
        ;
        $this->routeCompiled->expects($this->atLeastOnce())
            ->method('getRegex')
            ->will($this->returnValue('#'.str_replace('/', '\/', $this->url).'#'))
        ;
        $this->routeDocument = $this->getMockBuilder('Symfony\Component\Routing\Route')->disableOriginalConstructor()->getMock();
        $this->routeDocument->expects($this->atLeastOnce())
            ->method('compile')
            ->will($this->returnValue($this->routeCompiled))
        ;
        $this->routeDocument->expects($this->never())
            ->method('getRouteKey')
        ;
        $this->routeDocument->expects($this->atLeastOnce())
            ->method('getDefaults')
            ->will($this->returnValue(array('foo' => 'bar')))
        ;

        $mockCompiled = $this->buildMock('Symfony\Component\Routing\CompiledRoute');
        $mockCompiled->expects($this->any())
            ->method('getStaticPrefix')
            ->will($this->returnValue('/no/match'))
        ;
        $mockRoute = $this->getMockBuilder('Symfony\Component\Routing\Route')->disableOriginalConstructor()->getMock();
        $mockRoute->expects($this->any())
            ->method('compile')
            ->will($this->returnValue($mockCompiled))
        ;
        $routeCollection = new RouteCollection();
        $routeCollection->add('some', $mockRoute);
        $routeCollection->add('_company_more', $this->routeDocument);
        $routeCollection->add('other', $mockRoute);

        $results = $this->matcher->finalMatch($routeCollection, $this->request);

        $expected = array(
            RouteObjectInterface::ROUTE_NAME => '_company_more',
            RouteObjectInterface::ROUTE_OBJECT => $this->routeDocument,
            'foo' => 'bar',
        );

        $this->assertEquals($expected, $results);
    }
}
