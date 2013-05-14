<?php

namespace Symfony\Cmf\Component\Routing\Tests\Routing;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Route;

use Symfony\Cmf\Component\Routing\ProviderBasedGenerator;
use Symfony\Cmf\Component\Routing\Test\CmfUnitTestCase;

class ProviderBasedGeneratorTest extends CmfUnitTestCase
{
    protected $routeDocument;
    protected $routeCompiled;
    protected $provider;

    /** @var ProviderBasedGenerator */
    protected $generator;
    protected $context;

    public function setUp()
    {
        $this->routeDocument = $this->buildMock('Symfony\\Component\\Routing\\Route', array('getDefaults', 'compile'));
        $this->routeCompiled = $this->buildMock('Symfony\\Component\\Routing\\CompiledRoute');
        $this->provider = $this->buildMock("Symfony\\Cmf\\Component\\Routing\\RouteProviderInterface");
        $this->context = $this->buildMock('Symfony\\Component\\Routing\\RequestContext');

        $this->generator= new TestableProviderBasedGenerator($this->provider);
    }

    public function testGenerateFromName()
    {
        $name = 'foo/bar';

        $this->provider->expects($this->once())
            ->method('getRouteByName')
            ->with($name)
            ->will($this->returnValue($this->routeDocument))
        ;
        $this->routeDocument->expects($this->once())
            ->method('compile')
            ->will($this->returnValue($this->routeCompiled))
        ;

        $this->assertEquals('result_url', $this->generator->generate($name));
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGenerateNotFound()
    {
        $name = 'foo/bar';

        $this->provider->expects($this->once())
            ->method('getRouteByName')
            ->with($name)
            ->will($this->returnValue(null))
        ;

        $this->generator->generate($name);
    }

    public function testGenerateFromRoute()
    {
        $this->provider->expects($this->never())
            ->method('getRouteByName')
        ;
        $this->routeDocument->expects($this->once())
            ->method('compile')
            ->will($this->returnValue($this->routeCompiled))
        ;

        $this->assertEquals('result_url', $this->generator->generate($this->routeDocument));
    }

    public function testSupports()
    {
        $this->assertTrue($this->generator->supports('foo/bar'));
        $this->assertTrue($this->generator->supports($this->routeDocument));
        $this->assertFalse($this->generator->supports($this));
    }

    public function testGetRouteDebugMessage()
    {
        $this->assertContains('/some/key', $this->generator->getRouteDebugMessage(new RouteObject()));
        $this->assertContains('/de/test', $this->generator->getRouteDebugMessage(new Route('/de/test')));
        $this->assertContains('/some/route', $this->generator->getRouteDebugMessage('/some/route'));
    }
}

/**
 * Overwrite doGenerate to reduce amount of mocking needed
 */
class TestableProviderBasedGenerator extends ProviderBasedGenerator
{
    protected function doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $absolute, $hostTokens = null)
    {
        return 'result_url';
    }
}

class RouteObject implements RouteObjectInterface
{
    public function getRouteKey()
    {
        return '/some/key';
    }

    public function getRouteContent()
    {
        return null;
    }
}
