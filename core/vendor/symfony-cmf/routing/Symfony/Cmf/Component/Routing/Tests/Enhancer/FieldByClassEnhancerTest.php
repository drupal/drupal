<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2013 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Symfony\Cmf\Component\Routing\Tests\Enhancer;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Cmf\Component\Routing\Test\CmfUnitTestCase;
use Symfony\Cmf\Component\Routing\Enhancer\FieldByClassEnhancer;

class FieldByClassEnhancerTest extends CmfUnitTestCase
{
    private $request;
    /**
     * @var FieldByClassEnhancer
     */
    private $mapper;
    private $document;

    public function setUp()
    {
        $this->document = $this->buildMock('Symfony\Cmf\Component\Routing\Tests\Enhancer\RouteObject');

        $mapping = array('Symfony\Cmf\Component\Routing\Tests\Enhancer\RouteObject'
                            => 'cmf_content.controller:indexAction');

        $this->mapper = new FieldByClassEnhancer('_content', '_controller', $mapping);

        $this->request = Request::create('/test');
    }

    public function testClassFoundInMapping()
    {
        // this is the mock, thus a child class to make sure we properly check with instanceof
        $defaults = array('_content' => $this->document);
        $expected = array(
            '_content' => $this->document,
            '_controller' => 'cmf_content.controller:indexAction',
        );
        $this->assertEquals($expected, $this->mapper->enhance($defaults, $this->request));
    }

    public function testFieldAlreadyThere()
    {
        $defaults = array(
            '_content' => $this->document,
            '_controller' => 'custom.controller:indexAction',
        );
        $this->assertEquals($defaults, $this->mapper->enhance($defaults, $this->request));
    }

    public function testClassNotFoundInMapping()
    {
        $defaults = array('_content' => $this);
        $this->assertEquals($defaults, $this->mapper->enhance($defaults, $this->request));
    }

    public function testNoClass()
    {
        $defaults = array('foo' => 'bar');
        $this->assertEquals($defaults, $this->mapper->enhance($defaults, $this->request));
    }
}
