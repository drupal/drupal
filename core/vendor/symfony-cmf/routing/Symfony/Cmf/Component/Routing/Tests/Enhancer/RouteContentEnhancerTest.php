<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\Routing\Tests\Enhancer;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Cmf\Component\Routing\Enhancer\RouteContentEnhancer;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

use Symfony\Cmf\Component\Routing\Test\CmfUnitTestCase;

class RouteContentEnhancerTest extends CmfUnitTestCase
{
    /**
     * @var RouteContentEnhancer
     */
    private $mapper;
    private $document;
    private $request;

    public function setUp()
    {
        $this->document = $this->buildMock('Symfony\Cmf\Component\Routing\Tests\Enhancer\RouteObject',
                                            array('getContent', 'getRouteDefaults', 'getUrl'));

        $this->mapper = new RouteContentEnhancer(RouteObjectInterface::ROUTE_OBJECT, '_content');

        $this->request = Request::create('/test');
    }

    public function testContent()
    {
        $targetDocument = new TargetDocument();
        $this->document->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($targetDocument));

        $defaults = array(RouteObjectInterface::ROUTE_OBJECT => $this->document);
        $expected = array(RouteObjectInterface::ROUTE_OBJECT => $this->document, '_content' => $targetDocument);

        $this->assertEquals($expected, $this->mapper->enhance($defaults, $this->request));
    }

    public function testFieldAlreadyThere()
    {
        $this->document->expects($this->never())
            ->method('getContent')
        ;

        $defaults = array(RouteObjectInterface::ROUTE_OBJECT => $this->document, '_content' => 'foo');

        $this->assertEquals($defaults, $this->mapper->enhance($defaults, $this->request));
    }

    public function testNoContent()
    {
        $this->document->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue(null));

        $defaults = array(RouteObjectInterface::ROUTE_OBJECT => $this->document);
        $this->assertEquals($defaults, $this->mapper->enhance($defaults, $this->request));
    }

    public function testNoCmfRoute()
    {
        $defaults = array(RouteObjectInterface::ROUTE_OBJECT => $this->buildMock('Symfony\Component\Routing\Route'));
        $this->assertEquals($defaults, $this->mapper->enhance($defaults, $this->request));
    }
}

class TargetDocument
{
}

class UnknownDocument
{
}
