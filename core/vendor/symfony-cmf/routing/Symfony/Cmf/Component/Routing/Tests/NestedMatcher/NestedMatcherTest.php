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
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

use Symfony\Cmf\Component\Routing\NestedMatcher\NestedMatcher;

use Symfony\Cmf\Component\Routing\Test\CmfUnitTestCase;

class NestedMatcherTest extends CmfUnitTestCase
{
    private $provider;
    private $routeFilter1;
    private $routeFilter2;
    private $finalMatcher;

    public function setUp()
    {
        $this->provider = $this->buildMock('Symfony\Cmf\Component\Routing\RouteProviderInterface');
        $this->routeFilter1 = $this->buildMock('Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface');
        $this->routeFilter2 = $this->buildMock('Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface');
        $this->finalMatcher = $this->buildMock('Symfony\Cmf\Component\Routing\NestedMatcher\FinalMatcherInterface');
    }

    public function testNestedMatcher()
    {
        $request = Request::create('/path/one');
        $routeCollection = new RouteCollection();
        $route = $this->getMockBuilder('Symfony\Component\Routing\Route')->disableOriginalConstructor()->getMock();
        $routeCollection->add('route', $route);

        $this->provider->expects($this->once())
            ->method('getRouteCollectionForRequest')
            ->with($request)
            ->will($this->returnValue($routeCollection))
        ;
        $this->routeFilter1->expects($this->once())
            ->method('filter')
            ->with($routeCollection, $request)
            ->will($this->returnValue($routeCollection))
        ;
        $this->routeFilter2->expects($this->once())
            ->method('filter')
            ->with($routeCollection, $request)
            ->will($this->returnValue($routeCollection))
        ;
        $this->finalMatcher->expects($this->once())
            ->method('finalMatch')
            ->with($routeCollection, $request)
            ->will($this->returnValue(array('foo' => 'bar')))
        ;

        $matcher = new NestedMatcher($this->provider, $this->finalMatcher);
        $matcher->addRouteFilter($this->routeFilter1);
        $matcher->addRouteFilter($this->routeFilter2);

        $attributes = $matcher->matchRequest($request);

        $this->assertEquals(array('foo' => 'bar'), $attributes);
    }

    /**
     * Test priorities and exception handling
     */
    public function testNestedMatcherPriority()
    {
        $request = Request::create('/path/one');
        $routeCollection = new RouteCollection();
        $route = $this->getMockBuilder('Symfony\Component\Routing\Route')->disableOriginalConstructor()->getMock();
        $routeCollection->add('route', $route);

        $wrongProvider = $this->buildMock('Symfony\Cmf\Component\Routing\RouteProviderInterface');
        $wrongProvider->expects($this->never())
            ->method('getRouteCollectionForRequest')
        ;
        $this->provider->expects($this->once())
            ->method('getRouteCollectionForRequest')
            ->with($request)
            ->will($this->returnValue($routeCollection))
        ;
        $this->routeFilter1->expects($this->once())
            ->method('filter')
            ->with($routeCollection, $request)
            ->will($this->throwException(new ResourceNotFoundException()))
        ;
        $this->routeFilter2->expects($this->never())
            ->method('filter')
        ;
        $this->finalMatcher->expects($this->never())
            ->method('finalMatch')
        ;

        $matcher = new NestedMatcher($wrongProvider, $this->finalMatcher);
        $matcher->setRouteProvider($this->provider);
        $matcher->addRouteFilter($this->routeFilter2, 10);
        $matcher->addRouteFilter($this->routeFilter1, 20);

        try {
            $matcher->matchRequest($request);
            fail('nested matcher is eating exception');
        } catch (ResourceNotFoundException $e) {
            // expected
        }
    }

    public function testProviderNoMatch()
    {
        $request = Request::create('/path/one');
        $routeCollection = new RouteCollection();
        $this->provider->expects($this->once())
            ->method('getRouteCollectionForRequest')
            ->with($request)
            ->will($this->returnValue($routeCollection))
        ;
        $this->finalMatcher->expects($this->never())
            ->method('finalMatch')
        ;

        $matcher = new NestedMatcher($this->provider, $this->finalMatcher);

        $this->setExpectedException('Symfony\Component\Routing\Exception\ResourceNotFoundException');
        $matcher->matchRequest($request);
    }

}
