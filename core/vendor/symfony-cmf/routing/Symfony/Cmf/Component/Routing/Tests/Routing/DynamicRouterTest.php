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

use Symfony\Cmf\Component\Routing\Event\Events;
use Symfony\Cmf\Component\Routing\Event\RouterMatchEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

use Symfony\Cmf\Component\Routing\DynamicRouter;

use Symfony\Cmf\Component\Routing\Test\CmfUnitTestCase;

class DynamicRouterTest extends CmfUnitTestCase
{
    protected $routeDocument;
    protected $matcher;
    protected $generator;
    protected $enhancer;
    /** @var DynamicRouter */
    protected $router;
    protected $context;
    public $request;

    protected $url = '/foo/bar';

    public function setUp()
    {
        $this->routeDocument = $this->buildMock('Symfony\Cmf\Component\Routing\Tests\Routing\RouteMock', array('getDefaults'));

        $this->matcher = $this->buildMock('Symfony\Component\Routing\Matcher\UrlMatcherInterface');
        $this->generator = $this->buildMock('Symfony\Cmf\Component\Routing\VersatileGeneratorInterface', array('supports', 'generate', 'setContext', 'getContext', 'getRouteDebugMessage'));
        $this->enhancer = $this->buildMock('Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface', array('enhance'));

        $this->context = $this->buildMock('Symfony\Component\Routing\RequestContext');
        $this->request = Request::create($this->url);

        $this->router = new DynamicRouter($this->context, $this->matcher, $this->generator);
        $this->router->addRouteEnhancer($this->enhancer);
    }

    /**
     * rather trivial, but we want 100% coverage
     */
    public function testContext()
    {
        $this->router->setContext($this->context);
        $this->assertSame($this->context, $this->router->getContext());
    }

    public function testRouteCollection()
    {
        $collection = $this->router->getRouteCollection();
        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $collection);
        // TODO: once this is implemented, check content of collection
    }

    /// generator tests ///

    public function testGetGenerator()
    {
        $this->generator->expects($this->once())
            ->method('setContext')
            ->with($this->equalTo($this->context));

        $generator = $this->router->getGenerator();
        $this->assertInstanceOf('Symfony\Component\Routing\Generator\UrlGeneratorInterface', $generator);
        $this->assertSame($this->generator, $generator);
    }

    public function testGenerate()
    {
        $name = 'my_route_name';
        $parameters = array('foo' => 'bar');
        $absolute = true;

        $this->generator->expects($this->once())
            ->method('generate')
            ->with($name, $parameters, $absolute)
            ->will($this->returnValue('http://test'))
        ;

        $url = $this->router->generate($name, $parameters, $absolute);
        $this->assertEquals('http://test', $url);
    }

    public function testSupports()
    {
        $name = 'foo/bar';
        $this->generator->expects($this->once())
            ->method('supports')
            ->with($this->equalTo($name))
            ->will($this->returnValue(true))
        ;

        $this->assertTrue($this->router->supports($name));
    }

    public function testSupportsNonversatile()
    {
        $generator = $this->buildMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface', array('generate', 'setContext', 'getContext'));
        $router = new DynamicRouter($this->context, $this->matcher, $generator);
        $this->assertInternalType('string', $router->getRouteDebugMessage('test'));

        $this->assertTrue($router->supports('some string'));
        $this->assertFalse($router->supports($this));
    }

    /// match tests ///

    public function testGetMatcher()
    {
        $this->matcher->expects($this->once())
            ->method('setContext')
            ->with($this->equalTo($this->context));

        $matcher = $this->router->getMatcher();
        $this->assertInstanceOf('Symfony\Component\Routing\Matcher\UrlMatcherInterface', $matcher);
        $this->assertSame($this->matcher, $matcher);
    }

    public function testMatchUrl()
    {
        $routeDefaults = array('foo' => 'bar');
        $this->matcher->expects($this->once())
            ->method('match')
            ->with($this->url)
            ->will($this->returnValue($routeDefaults))
        ;

        $expected = array('this' => 'that');
        $this->enhancer->expects($this->once())
            ->method('enhance')
            ->with($this->equalTo($routeDefaults), $this->equalTo($this->request))
            ->will($this->returnValue($expected))
        ;

        $results = $this->router->match($this->url);

        $this->assertEquals($expected, $results);
    }

    public function testMatchRequestWithUrlMatcher()
    {
        $routeDefaults = array('foo' => 'bar');

        $this->matcher->expects($this->once())
            ->method('match')
            ->with($this->url)
            ->will($this->returnValue($routeDefaults))
        ;

        $expected = array('this' => 'that');
        $this->enhancer->expects($this->once())
            ->method('enhance')
            // somehow request object gets confused, check on instance only
            ->with($this->equalTo($routeDefaults), $this->isInstanceOf('Symfony\Component\HttpFoundation\Request'))
            ->will($this->returnValue($expected))
        ;

        $results = $this->router->matchRequest($this->request);

        $this->assertEquals($expected, $results);
    }

    public function testMatchRequest()
    {
        $routeDefaults = array('foo' => 'bar');

        $matcher = $this->buildMock('Symfony\Component\Routing\Matcher\RequestMatcherInterface', array('matchRequest', 'setContext', 'getContext'));
        $router = new DynamicRouter($this->context, $matcher, $this->generator);

        $matcher->expects($this->once())
            ->method('matchRequest')
            ->with($this->request)
            ->will($this->returnValue($routeDefaults))
        ;

        $expected = array('this' => 'that');
        $this->enhancer->expects($this->once())
            ->method('enhance')
            ->with($this->equalTo($routeDefaults), $this->equalTo($this->request))
            ->will($this->returnValue($expected))
        ;

        $router->addRouteEnhancer($this->enhancer);

        $this->assertEquals($expected, $router->matchRequest($this->request));
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testMatchFilter()
    {
        $router = new DynamicRouter($this->context, $this->matcher, $this->generator, '#/different/prefix.*#');
        $router->addRouteEnhancer($this->enhancer);

        $this->matcher->expects($this->never())
            ->method('match')
        ;

        $this->enhancer->expects($this->never())
            ->method('enhance')
        ;

        $router->match($this->url);
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testMatchRequestFilter()
    {
        $matcher = $this->buildMock('Symfony\Component\Routing\Matcher\RequestMatcherInterface', array('matchRequest', 'setContext', 'getContext'));

        $router = new DynamicRouter($this->context, $matcher, $this->generator, '#/different/prefix.*#');
        $router->addRouteEnhancer($this->enhancer);

        $matcher->expects($this->never())
            ->method('matchRequest')
        ;

        $this->enhancer->expects($this->never())
            ->method('enhance')
        ;

        $router->matchRequest($this->request);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMatchUrlWithRequestMatcher()
    {
        $matcher = $this->buildMock('Symfony\Component\Routing\Matcher\RequestMatcherInterface', array('matchRequest', 'setContext', 'getContext'));
        $router = new DynamicRouter($this->context, $matcher, $this->generator);

        $router->match($this->url);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidMatcher()
    {
        new DynamicRouter($this->context, $this, $this->generator);
    }

    public function testRouteDebugMessage()
    {
        $this->generator->expects($this->once())
            ->method('getRouteDebugMessage')
            ->with($this->equalTo('test'), $this->equalTo(array()))
            ->will($this->returnValue('debug message'))
        ;

        $this->assertEquals('debug message', $this->router->getRouteDebugMessage('test'));
    }

    public function testRouteDebugMessageNonversatile()
    {
        $generator = $this->buildMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface', array('generate', 'setContext', 'getContext'));
        $router = new DynamicRouter($this->context, $this->matcher, $generator);
        $this->assertInternalType('string', $router->getRouteDebugMessage('test'));
    }

    public function testEventHandler()
    {
        $eventDispatcher = $this->buildMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $router = new DynamicRouter($this->context, $this->matcher, $this->generator, '', $eventDispatcher);

        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(Events::PRE_DYNAMIC_MATCH, $this->equalTo(new RouterMatchEvent()))
        ;

        $routeDefaults = array('foo' => 'bar');
        $this->matcher->expects($this->once())
            ->method('match')
            ->with($this->url)
            ->will($this->returnValue($routeDefaults))
        ;

        $this->assertEquals($routeDefaults, $router->match($this->url));
    }

    public function testEventHandlerRequest()
    {
        $eventDispatcher = $this->buildMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $router = new DynamicRouter($this->context, $this->matcher, $this->generator, '', $eventDispatcher);

        $that = $this;
        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(Events::PRE_DYNAMIC_MATCH_REQUEST, $this->callback(function($event) use ($that) {
                $that->assertInstanceOf('Symfony\Cmf\Component\Routing\Event\RouterMatchEvent', $event);
                $that->assertEquals($that->request, $event->getRequest());

                return true;
            }))
        ;

        $routeDefaults = array('foo' => 'bar');
        $this->matcher->expects($this->once())
            ->method('match')
            ->with($this->url)
            ->will($this->returnValue($routeDefaults))
        ;

        $this->assertEquals($routeDefaults, $router->matchRequest($this->request));
    }
}
