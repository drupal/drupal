<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\Routing\Tests\Routing;

use Symfony\Cmf\Component\Routing\RouteReferrersReadInterface;

use Symfony\Cmf\Component\Routing\ContentAwareGenerator;
use Symfony\Cmf\Component\Routing\Test\CmfUnitTestCase;

class ContentAwareGeneratorTest extends CmfUnitTestCase
{
    protected $contentDocument;
    protected $routeDocument;
    protected $routeCompiled;
    protected $provider;

    /**
     * @var ContentAwareGenerator
     */
    protected $generator;
    protected $context;

    public function setUp()
    {
        $this->contentDocument = $this->buildMock('Symfony\Cmf\Component\Routing\RouteReferrersReadInterface');
        $this->routeDocument = $this->buildMock('Symfony\Cmf\Component\Routing\Tests\Routing\RouteMock', array('getDefaults', 'compile'));
        $this->routeCompiled = $this->buildMock('Symfony\Component\Routing\CompiledRoute');
        $this->provider = $this->buildMock('Symfony\Cmf\Component\Routing\RouteProviderInterface');
        $this->context = $this->buildMock('Symfony\Component\Routing\RequestContext');

        $this->generator = new TestableContentAwareGenerator($this->provider);
    }

    public function testGenerateFromContent()
    {
        $this->provider->expects($this->never())
            ->method('getRouteByName')
        ;
        $this->contentDocument->expects($this->once())
            ->method('getRoutes')
            ->will($this->returnValue(array($this->routeDocument)))
        ;
        $this->routeDocument->expects($this->once())
            ->method('compile')
            ->will($this->returnValue($this->routeCompiled))
        ;

        $this->assertEquals('result_url', $this->generator->generate($this->contentDocument));
    }

    public function testGenerateFromContentId()
    {
        $this->provider->expects($this->never())
            ->method('getRouteByName')
        ;

        $contentRepository = $this->buildMock('Symfony\Cmf\Component\Routing\ContentRepositoryInterface', array('findById', 'getContentId'));
        $contentRepository->expects($this->once())
            ->method('findById')
            ->with('/content/id')
            ->will($this->returnValue($this->contentDocument))
        ;
        $this->generator->setContentRepository($contentRepository);

        $this->contentDocument->expects($this->once())
            ->method('getRoutes')
            ->will($this->returnValue(array($this->routeDocument)))
        ;

        $this->routeDocument->expects($this->once())
            ->method('compile')
            ->will($this->returnValue($this->routeCompiled))
        ;

        $this->assertEquals('result_url', $this->generator->generate('', array('content_id' => '/content/id')));
    }

    public function testGenerateEmptyRouteString()
    {
        $this->provider->expects($this->never())
            ->method('getRouteByName')
        ;

        $this->contentDocument->expects($this->once())
            ->method('getRoutes')
            ->will($this->returnValue(array($this->routeDocument)))
        ;

        $this->routeDocument->expects($this->once())
            ->method('compile')
            ->will($this->returnValue($this->routeCompiled))
        ;

        $this->assertEquals('result_url', $this->generator->generate($this->contentDocument));
    }

    public function testGenerateRouteMultilang()
    {
        $route_en = $this->buildMock('Symfony\Cmf\Component\Routing\Tests\Routing\RouteMock', array('getDefaults', 'compile', 'getContent'));
        $route_en->setLocale('en');
        $route_de = $this->routeDocument;
        $route_de->setLocale('de');

        $this->contentDocument->expects($this->once())
            ->method('getRoutes')
            ->will($this->returnValue(array($route_en, $route_de)))
        ;
        $route_en->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($this->contentDocument))
        ;
        $route_en->expects($this->never())
            ->method('compile')
        ;
        $route_de->expects($this->once())
            ->method('compile')
            ->will($this->returnValue($this->routeCompiled))
        ;

        $this->assertEquals('result_url', $this->generator->generate($route_en, array('_locale' => 'de')));
    }

    public function testGenerateRouteMultilangDefaultLocale()
    {
        $route = $this->buildMock('Symfony\Cmf\Component\Routing\Tests\Routing\RouteMock');
        $route->expects($this->any())
            ->method('compile')
            ->will($this->returnValue($this->routeCompiled))
        ;
        $route->expects($this->any())
            ->method('getRequirement')
            ->with('_locale')
            ->will($this->returnValue('de|en'))
        ;
        $route->expects($this->any())
            ->method('getDefault')
            ->with('_locale')
            ->will($this->returnValue('en'))
        ;
        $this->routeCompiled->expects($this->any())
            ->method('getVariables')
            ->will($this->returnValue(array()))
        ;

        $this->assertEquals('result_url', $this->generator->generate($route, array('_locale' => 'en')));
    }

    public function testGenerateRouteMultilangLocaleNomatch()
    {
        $route_en = $this->buildMock('Symfony\Cmf\Component\Routing\Tests\Routing\RouteMock', array('getDefaults', 'compile', 'getContent'));
        $route_en->setLocale('en');
        $route_de = $this->routeDocument;
        $route_de->setLocale('de');

        $this->contentDocument->expects($this->once())
            ->method('getRoutes')
            ->will($this->returnValue(array($route_en, $route_de)))
        ;
        $route_en->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($this->contentDocument))
        ;
        $route_en->expects($this->once())
            ->method('compile')
            ->will($this->returnValue($this->routeCompiled))
        ;
        $route_de->expects($this->never())
            ->method('compile')
        ;

        $this->assertEquals('result_url', $this->generator->generate($route_en, array('_locale' => 'fr')));
    }

    public function testGenerateNoncmfRouteMultilang()
    {
        $route_en = $this->buildMock('Symfony\Component\Routing\Route', array('getDefaults', 'compile', 'getContent'));

        $route_en->expects($this->once())
            ->method('compile')
            ->will($this->returnValue($this->routeCompiled))
        ;

        $this->assertEquals('result_url', $this->generator->generate($route_en, array('_locale' => 'de')));
    }

    public function testGenerateRoutenameMultilang()
    {
        $name = 'foo/bar';
        $route_en = $this->buildMock('Symfony\Cmf\Component\Routing\Tests\Routing\RouteMock', array('getDefaults', 'compile', 'getContent'));
        $route_en->setLocale('en');
        $route_de = $this->routeDocument;
        $route_de->setLocale('de');

        $this->provider->expects($this->once())
            ->method('getRouteByName')
            ->with($name)
            ->will($this->returnValue($route_en))
        ;
        $this->contentDocument->expects($this->once())
            ->method('getRoutes')
            ->will($this->returnValue(array($route_en, $route_de)))
        ;
        $route_en->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($this->contentDocument))
        ;
        $route_en->expects($this->never())
            ->method('compile')
        ;
        $route_de->expects($this->once())
            ->method('compile')
            ->will($this->returnValue($this->routeCompiled))
        ;

        $this->assertEquals('result_url', $this->generator->generate($name, array('_locale' => 'de')));
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGenerateRoutenameMultilangNotFound()
    {
        $name = 'foo/bar';

        $this->provider->expects($this->once())
            ->method('getRouteByName')
            ->with($name)
            ->will($this->returnValue(null))
        ;

        $this->generator->generate($name, array('_locale' => 'de'));
    }

    public function testGenerateDocumentMultilang()
    {
        $route_en = $this->buildMock('Symfony\Cmf\Component\Routing\Tests\Routing\RouteMock', array('getDefaults', 'compile'));
        $route_en->setLocale('en');
        $route_de = $this->routeDocument;
        $route_de->setLocale('de');

        $this->contentDocument->expects($this->once())
            ->method('getRoutes')
            ->will($this->returnValue(array($route_en, $route_de)))
        ;
        $route_en->expects($this->never())
            ->method('compile')
        ;
        $route_de->expects($this->once())
            ->method('compile')
            ->will($this->returnValue($this->routeCompiled))
        ;

        $this->assertEquals('result_url', $this->generator->generate($this->contentDocument, array('_locale' => 'de')));
    }

    public function testGenerateDocumentMultilangLocaleNomatch()
    {
        $route_en = $this->buildMock('Symfony\Cmf\Component\Routing\Tests\Routing\RouteMock', array('getDefaults', 'compile'));
        $route_en->setLocale('en');
        $route_de = $this->routeDocument;
        $route_de->setLocale('de');

        $this->contentDocument->expects($this->once())
            ->method('getRoutes')
            ->will($this->returnValue(array($route_en, $route_de)))
        ;
        $route_en->expects($this->once())
            ->method('compile')
            ->will($this->returnValue($this->routeCompiled))
        ;
        $route_de->expects($this->never())
            ->method('compile')
        ;

        $this->assertEquals('result_url', $this->generator->generate($this->contentDocument, array('_locale' => 'fr')));
    }

    /**
     * Generate without any information.
     *
     * @expectedException \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGenerateNoContent()
    {
        $this->generator->generate('', array());
    }

    /**
     * Generate with an object that is neither a route nor route aware.
     *
     * @expectedException \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGenerateInvalidContent()
    {
        $this->generator->generate($this);
    }

    /**
     * Generate with a content_id but there is no content repository.
     *
     * @expectedException \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGenerateNoContentRepository()
    {
        $this->provider->expects($this->never())
            ->method('getRouteByName')
        ;

        $this->generator->generate('', array('content_id' => '/content/id'));
    }

    /**
     * Generate with content_id but the content is not found.
     *
     * @expectedException \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGenerateNoContentFoundInRepository()
    {
        $this->provider->expects($this->never())
            ->method('getRouteByName')
        ;

        $contentRepository = $this->buildMock('Symfony\Cmf\Component\Routing\ContentRepositoryInterface', array('findById', 'getContentId'));
        $contentRepository->expects($this->once())
            ->method('findById')
            ->with('/content/id')
            ->will($this->returnValue(null))
        ;
        $this->generator->setContentRepository($contentRepository);

        $this->generator->generate('', array('content_id' => '/content/id'));
    }

    /**
     * Generate with content_id but the object at id is not route aware.
     *
     * @expectedException \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGenerateWrongContentClassInRepository()
    {
        $this->provider->expects($this->never())
            ->method('getRouteByName')
        ;

        $contentRepository = $this->buildMock('Symfony\Cmf\Component\Routing\ContentRepositoryInterface', array('findById', 'getContentId'));
        $contentRepository->expects($this->once())
            ->method('findById')
            ->with('/content/id')
            ->will($this->returnValue($this))
        ;
        $this->generator->setContentRepository($contentRepository);

        $this->generator->generate('', array('content_id' => '/content/id'));
    }

    /**
     * Generate from a content that has no routes associated.
     *
     * @expectedException \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGenerateNoRoutes()
    {
        $this->contentDocument->expects($this->once())
            ->method('getRoutes')
            ->will($this->returnValue(array()));

        $this->generator->generate($this->contentDocument);
    }
    /**
     * Generate from a content that returns something that is not a route as route.
     *
     * @expectedException \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGenerateInvalidRoute()
    {
        $this->contentDocument->expects($this->once())
            ->method('getRoutes')
            ->will($this->returnValue(array($this)));

        $this->generator->generate($this->contentDocument);
    }

    public function testGetLocaleAttribute()
    {
        $this->generator->setDefaultLocale('en');

        $attributes = array('_locale' => 'fr');
        $this->assertEquals('fr', $this->generator->getLocale($attributes));
    }

    public function testGetLocaleDefault()
    {
        $this->generator->setDefaultLocale('en');

        $attributes = array();
        $this->assertEquals('en', $this->generator->getLocale($attributes));
    }

    public function testGetLocaleContext()
    {
        $this->generator->setDefaultLocale('en');

        $this->generator->getContext()->setParameter('_locale', 'de');

        $attributes = array();
        $this->assertEquals('de', $this->generator->getLocale($attributes));
    }

    public function testSupports()
    {
        $this->assertTrue($this->generator->supports(''));
        $this->assertTrue($this->generator->supports(null));
        $this->assertTrue($this->generator->supports($this->contentDocument));
        $this->assertFalse($this->generator->supports($this));
    }

    public function testGetRouteDebugMessage()
    {
        $this->assertContains('/some/content', $this->generator->getRouteDebugMessage(null, array('content_id' => '/some/content')));
        $this->assertContains('Route aware content Symfony\Cmf\Component\Routing\Tests\Routing\RouteAware', $this->generator->getRouteDebugMessage(new RouteAware()));
        $this->assertContains('/some/content', $this->generator->getRouteDebugMessage('/some/content'));
    }
}

/**
 * Overwrite doGenerate to reduce amount of mocking needed
 */
class TestableContentAwareGenerator extends ContentAwareGenerator
{
    protected function doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $referenceType, $hostTokens, array $requiredSchemes = array())
    {
        return 'result_url';
    }

    // expose as public
    public function getLocale($parameters)
    {
        return parent::getLocale($parameters);
    }
}

class RouteAware implements RouteReferrersReadInterface
{
    public function getRoutes()
    {
        return array();
    }
}
