<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2013 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Symfony\Cmf\Component\Routing\Tests\Routing;

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Cmf\Component\Routing\ChainRouter;
use Symfony\Cmf\Component\Routing\Test\CmfUnitTestCase;

class ChainRouterTest extends CmfUnitTestCase
{
    public function setUp()
    {
        $this->router = new ChainRouter($this->getMock('Psr\Log\LoggerInterface'));
        $this->context = $this->getMock('Symfony\Component\Routing\RequestContext');
    }

    public function testPriority()
    {
        $this->assertEquals(array(), $this->router->all());

        list($low, $high) = $this->createRouterMocks();

        $this->router->add($low, 10);
        $this->router->add($high, 100);

        $this->assertEquals(array(
            $high,
            $low,
        ), $this->router->all());
    }

    /**
     * Routers are supposed to be sorted only once.
     * This test will check that by trying to get all routers several times.
     *
     * @covers \Symfony\Cmf\Component\Routing\ChainRouter::sortRouters
     * @covers \Symfony\Cmf\Component\Routing\ChainRouter::all
     */
    public function testSortRouters()
    {
        list($low, $medium, $high) = $this->createRouterMocks();
        // We're using a mock here and not $this->router because we need to ensure that the sorting operation is done only once.
        $router = $this->buildMock('Symfony\Cmf\Component\Routing\ChainRouter', array('sortRouters'));
        $router
            ->expects($this->once())
            ->method('sortRouters')
            ->will(
                $this->returnValue(
                    array($high, $medium, $low)
                )
            )
        ;

        $router->add($low, 10);
        $router->add($medium, 50);
        $router->add($high, 100);
        $expectedSortedRouters = array($high, $medium, $low);
        // Let's get all routers 5 times, we should only sort once.
        for ($i = 0; $i < 5; ++$i) {
            $this->assertSame($expectedSortedRouters, $router->all());
        }
    }

    /**
     * This test ensures that if a router is being added on the fly, the sorting is reset.
     *
     * @covers \Symfony\Cmf\Component\Routing\ChainRouter::sortRouters
     * @covers \Symfony\Cmf\Component\Routing\ChainRouter::all
     * @covers \Symfony\Cmf\Component\Routing\ChainRouter::add
     */
    public function testReSortRouters()
    {
        list($low, $medium, $high) = $this->createRouterMocks();
        $highest = clone $high;
        // We're using a mock here and not $this->router because we need to ensure that the sorting operation is done only once.
        $router = $this->buildMock('Symfony\Cmf\Component\Routing\ChainRouter', array('sortRouters'));
        $router
            ->expects($this->at(0))
            ->method('sortRouters')
            ->will(
                $this->returnValue(
                    array($high, $medium, $low)
                )
            )
        ;
        // The second time sortRouters() is called, we're supposed to get the newly added router ($highest)
        $router
            ->expects($this->at(1))
            ->method('sortRouters')
            ->will(
                $this->returnValue(
                    array($highest, $high, $medium, $low)
                )
            )
        ;

        $router->add($low, 10);
        $router->add($medium, 50);
        $router->add($high, 100);
        $this->assertSame(array($high, $medium, $low), $router->all());

        // Now adding another router on the fly, sorting must have been reset
        $router->add($highest, 101);
        $this->assertSame(array($highest, $high, $medium, $low), $router->all());
    }

    /**
     * context must be propagated to chained routers and be stored locally
     */
    public function testContext()
    {
        list($low, $high) = $this->createRouterMocks();

        $low
            ->expects($this->once())
            ->method('setContext')
            ->with($this->context)
        ;

        $high
            ->expects($this->once())
            ->method('setContext')
            ->with($this->context)
        ;

        $this->router->add($low, 10);
        $this->router->add($high, 100);

        $this->router->setContext($this->context);
        $this->assertSame($this->context, $this->router->getContext());
    }

    /**
     * context must be propagated also when routers are added after context is set
     */
    public function testContextOrder()
    {
        list($low, $high) = $this->createRouterMocks();

        $low
            ->expects($this->once())
            ->method('setContext')
            ->with($this->context)
        ;

        $high
            ->expects($this->once())
            ->method('setContext')
            ->with($this->context)
        ;

        $this->router->setContext($this->context);

        $this->router->add($low, 10);
        $this->router->add($high, 100);

        $this->router->all();

        $this->assertSame($this->context, $this->router->getContext());
    }

    /**
     * The first usable match is used, no further routers are queried once a match is found
     */
    public function testMatch()
    {
        $url = '/test';
        list($lower, $low, $high) = $this->createRouterMocks();

        $high
            ->expects($this->once())
            ->method('match')
            ->with($url)
            ->will($this->throwException(new \Symfony\Component\Routing\Exception\ResourceNotFoundException))
        ;
        $low
            ->expects($this->once())
            ->method('match')
            ->with($url)
            ->will($this->returnValue(array('test')))
        ;
        $lower
            ->expects($this->never())
            ->method('match');
        $this->router->add($lower, 5);
        $this->router->add($low, 10);
        $this->router->add($high, 100);

        $result = $this->router->match('/test');
        $this->assertEquals(array('test'), $result);
    }

    /**
     * The first usable match is used, no further routers are queried once a match is found
     */
    public function testMatchRequest()
    {
        $url = '/test';
        list($lower, $low, $high) = $this->createRouterMocks();

        $highest = $this->getMock('Symfony\Cmf\Component\Routing\Tests\Routing\RequestMatcher');

        $request = Request::create('/test');

        $highest
            ->expects($this->once())
            ->method('matchRequest')
            ->will($this->throwException(new \Symfony\Component\Routing\Exception\ResourceNotFoundException))
        ;
        $high
            ->expects($this->once())
            ->method('match')
            ->with($url)
            ->will($this->throwException(new \Symfony\Component\Routing\Exception\ResourceNotFoundException))
        ;
        $low
            ->expects($this->once())
            ->method('match')
            ->with($url)
            ->will($this->returnValue(array('test')))
        ;
        $lower
            ->expects($this->never())
            ->method('match')
        ;

        $this->router->add($lower, 5);
        $this->router->add($low, 10);
        $this->router->add($high, 100);
        $this->router->add($highest, 200);

        $result = $this->router->matchRequest($request);
        $this->assertEquals(array('test'), $result);
    }

    /**
     * Call match on ChainRouter that has RequestMatcher in the chain.
     */
    public function testMatchWithRequestMatchers()
    {
        $url = '/test';
        $request = Request::create('/test');

        list($low) = $this->createRouterMocks();

        $high = $this->getMock('Symfony\Cmf\Component\Routing\Tests\Routing\RequestMatcher');

        $high
            ->expects($this->once())
            ->method('matchRequest')
            ->with($request)
            ->will($this->throwException(new \Symfony\Component\Routing\Exception\ResourceNotFoundException))
        ;
        $low
            ->expects($this->once())
            ->method('match')
            ->with($url)
            ->will($this->returnValue(array('test')))
        ;

        $this->router->add($low, 10);
        $this->router->add($high, 20);

        $result = $this->router->match($url);
        $this->assertEquals(array('test'), $result);
    }

    /**
     * If there is a method not allowed but another router matches, that one is used
     */
    public function testMatchAndNotAllowed()
    {
        $url = '/test';
        list($low, $high) = $this->createRouterMocks();

        $high
            ->expects($this->once())
            ->method('match')
            ->with($url)
            ->will($this->throwException(new \Symfony\Component\Routing\Exception\MethodNotAllowedException(array())))
        ;
        $low
            ->expects($this->once())
            ->method('match')
            ->with($url)
            ->will($this->returnValue(array('test')))
        ;
        $this->router->add($low, 10);
        $this->router->add($high, 100);

        $result = $this->router->match('/test');
        $this->assertEquals(array('test'), $result);
    }

    /**
     * If there is a method not allowed but another router matches, that one is used
     */
    public function testMatchRequestAndNotAllowed()
    {
        $url = '/test';
        list($low, $high) = $this->createRouterMocks();

        $high
            ->expects($this->once())
            ->method('match')
            ->with($url)
            ->will($this->throwException(new \Symfony\Component\Routing\Exception\MethodNotAllowedException(array())))
        ;
        $low
            ->expects($this->once())
            ->method('match')
            ->with($url)
            ->will($this->returnValue(array('test')))
        ;
        $this->router->add($low, 10);
        $this->router->add($high, 100);

        $result = $this->router->matchRequest(Request::create('/test'));
        $this->assertEquals(array('test'), $result);
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testMatchNotFound()
    {
        $url = '/test';
        list($low, $high) = $this->createRouterMocks();

        $high
            ->expects($this->once())
            ->method('match')
            ->with($url)
            ->will($this->throwException(new ResourceNotFoundException()))
        ;
        $low
            ->expects($this->once())
            ->method('match')
            ->with($url)
            ->will($this->throwException(new ResourceNotFoundException()))
        ;
        $this->router->add($low, 10);
        $this->router->add($high, 100);

        $this->router->match('/test');
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testMatchRequestNotFound()
    {
        $url = '/test';
        list($low, $high) = $this->createRouterMocks();

        $high
            ->expects($this->once())
            ->method('match')
            ->with($url)
            ->will($this->throwException(new ResourceNotFoundException()))
        ;
        $low
            ->expects($this->once())
            ->method('match')
            ->with($url)
            ->will($this->throwException(new ResourceNotFoundException()))
        ;
        $this->router->add($low, 10);
        $this->router->add($high, 100);

        $this->router->matchRequest(Request::create('/test'));
    }

    /**
     * If any of the routers throws a not allowed exception and no other matches, we need to see this
     *
     * @expectedException \Symfony\Component\Routing\Exception\MethodNotAllowedException
     */
    public function testMatchMethodNotAllowed()
    {
        $url = '/test';
        list($low, $high) = $this->createRouterMocks();

        $high
            ->expects($this->once())
            ->method('match')
            ->with($url)
            ->will($this->throwException(new MethodNotAllowedException(array())))
        ;
        $low
            ->expects($this->once())
            ->method('match')
            ->with($url)
            ->will($this->throwException(new ResourceNotFoundException()))
        ;
        $this->router->add($low, 10);
        $this->router->add($high, 100);

        $this->router->match('/test');
    }

    /**
     * If any of the routers throws a not allowed exception and no other matches, we need to see this
     *
     * @expectedException \Symfony\Component\Routing\Exception\MethodNotAllowedException
     */
    public function testMatchRequestMethodNotAllowed()
    {
        $url = '/test';
        list($low, $high) = $this->createRouterMocks();

        $high
            ->expects($this->once())
            ->method('match')
            ->with($url)
            ->will($this->throwException(new MethodNotAllowedException(array())))
        ;
        $low
            ->expects($this->once())
            ->method('match')
            ->with($url)
            ->will($this->throwException(new ResourceNotFoundException()))
        ;
        $this->router->add($low, 10);
        $this->router->add($high, 100);

        $this->router->matchRequest(Request::create('/test'));
    }

    public function testGenerate()
    {
        $url = '/test';
        $name = 'test';
        $parameters = array('test' => 'value');
        list($lower, $low, $high) = $this->createRouterMocks();

        $high
            ->expects($this->once())
            ->method('generate')
            ->with($name, $parameters, false)
            ->will($this->throwException(new RouteNotFoundException()))
        ;
        $low
            ->expects($this->once())
            ->method('generate')
            ->with($name, $parameters, false)
            ->will($this->returnValue($url))
        ;
        $lower
            ->expects($this->never())
            ->method('generate')
        ;

        $this->router->add($low, 10);
        $this->router->add($high, 100);

        $result = $this->router->generate($name, $parameters);
        $this->assertEquals($url, $result);
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGenerateNotFound()
    {
        $name = 'test';
        $parameters = array('test' => 'value');
        list($low, $high) = $this->createRouterMocks();

        $high
            ->expects($this->once())
            ->method('generate')
            ->with($name, $parameters, false)
            ->will($this->throwException(new RouteNotFoundException()))
        ;
        $low->expects($this->once())
            ->method('generate')
            ->with($name, $parameters, false)
            ->will($this->throwException(new RouteNotFoundException()))
        ;
        $this->router->add($low, 10);
        $this->router->add($high, 100);

        $this->router->generate($name, $parameters);
    }

    /**
     * Route is an object but no versatile generator around to do the debug message.
     *
     * @expectedException \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGenerateObjectNotFound()
    {
        $name = new \stdClass();
        $parameters = array('test' => 'value');

        $defaultRouter = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $defaultRouter
            ->expects($this->never())
            ->method('generate')
        ;

        $this->router->add($defaultRouter, 200);

        $this->router->generate($name, $parameters);
    }

    /**
     * A versatile router will generate the debug message.
     *
     * @expectedException \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGenerateObjectNotFoundVersatile()
    {
        $name = new \stdClass();
        $parameters = array('test' => 'value');

        $chainedRouter = $this->getMock('Symfony\Cmf\Component\Routing\ChainedRouterInterface');
        $chainedRouter
            ->expects($this->once())
            ->method('supports')
            ->will($this->returnValue(true))
        ;
        $chainedRouter->expects($this->once())
            ->method('generate')
            ->with($name, $parameters, false)
            ->will($this->throwException(new RouteNotFoundException()))
        ;
        $chainedRouter->expects($this->once())
            ->method('getRouteDebugMessage')
            ->with($name, $parameters)
            ->will($this->returnValue('message'))
        ;

        $this->router->add($chainedRouter, 10);

        $this->router->generate($name, $parameters);
    }

    public function testGenerateObjectName()
    {
        $name = new \stdClass();
        $parameters = array('test' => 'value');

        $defaultRouter = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $chainedRouter = $this->getMock('Symfony\Cmf\Component\Routing\ChainedRouterInterface');

        $defaultRouter
            ->expects($this->never())
            ->method('generate')
        ;
        $chainedRouter
            ->expects($this->once())
            ->method('supports')
            ->will($this->returnValue(true))
        ;
        $chainedRouter
            ->expects($this->once())
            ->method('generate')
            ->with($name, $parameters, false)
            ->will($this->returnValue($name))
        ;

        $this->router->add($defaultRouter, 200);
        $this->router->add($chainedRouter, 100);

        $result = $this->router->generate($name, $parameters);
        $this->assertEquals($name, $result);
    }

    public function testWarmup()
    {
        $dir = 'test_dir';
        list($low) = $this->createRouterMocks();

        $low
            ->expects($this->never())
            ->method('warmUp')
        ;
        $high = $this->getMock('Symfony\Cmf\Component\Routing\Tests\Routing\WarmableRouterMock');
        $high
            ->expects($this->once())
            ->method('warmUp')
            ->with($dir)
        ;

        $this->router->add($low, 10);
        $this->router->add($high, 100);

        $this->router->warmUp($dir);
    }

    public function testRouteCollection()
    {
        list($low, $high) = $this->createRouterMocks();
        $lowcol = new RouteCollection();
        $lowcol->add('low', $this->buildMock('Symfony\Component\Routing\Route'));
        $highcol = new RouteCollection();
        $highcol->add('high', $this->buildMock('Symfony\Component\Routing\Route'));

        $low
            ->expects($this->once())
            ->method('getRouteCollection')
            ->will($this->returnValue($lowcol))
        ;
        $high
            ->expects($this->once())
            ->method('getRouteCollection')
            ->will($this->returnValue($highcol))
        ;

        $this->router->add($low, 10);
        $this->router->add($high, 100);

        $collection = $this->router->getRouteCollection();
        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $collection);

        $names = array();
        foreach ($collection->all() as $name => $route) {
            $this->assertInstanceOf('Symfony\Component\Routing\Route', $route);
            $names[] = $name;
        }
        $this->assertEquals(array('high', 'low'), $names);
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testSupport()
    {

        $router = $this->getMock('Symfony\Cmf\Component\Routing\ChainedRouterInterface');
        $router
            ->expects($this->once())
            ->method('supports')
            ->will($this->returnValue(false))
        ;

        $router
            ->expects($this->never())
            ->method('generate')
            ->will($this->returnValue(false))
        ;

        $this->router->add($router);

        $this->router->generate('foobar');
    }

    protected function createRouterMocks()
    {
        return array(
            $this->getMock('Symfony\Component\Routing\RouterInterface'),
            $this->getMock('Symfony\Component\Routing\RouterInterface'),
            $this->getMock('Symfony\Component\Routing\RouterInterface'),
        );
    }
}

abstract class WarmableRouterMock implements \Symfony\Component\Routing\RouterInterface, \Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface
{
}

abstract class RequestMatcher implements \Symfony\Component\Routing\RouterInterface, \Symfony\Component\Routing\Matcher\RequestMatcherInterface
{
}
