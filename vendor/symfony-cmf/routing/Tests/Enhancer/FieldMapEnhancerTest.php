<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\Routing\Tests\Mapper;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Cmf\Component\Routing\Test\CmfUnitTestCase;
use Symfony\Cmf\Component\Routing\Enhancer\FieldMapEnhancer;

class FieldMapEnhancerTest extends CmfUnitTestCase
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var FieldMapEnhancer
     */
    private $enhancer;

    public function setUp()
    {
        $this->request = Request::create('/test');
        $mapping = array('static_pages' => 'cmf_content.controller:indexAction');

        $this->enhancer = new FieldMapEnhancer('type', '_controller', $mapping);
    }

    public function testFieldFoundInMapping()
    {
        $defaults = array('type' => 'static_pages');
        $expected = array(
            'type' => 'static_pages',
            '_controller' => 'cmf_content.controller:indexAction',
        );
        $this->assertEquals($expected, $this->enhancer->enhance($defaults, $this->request));
    }

    public function testFieldAlreadyThere()
    {
        $defaults = array(
            'type' => 'static_pages',
            '_controller' => 'custom.controller:indexAction',
        );
        $this->assertEquals($defaults, $this->enhancer->enhance($defaults, $this->request));
    }

    public function testNoType()
    {
        $defaults = array();
        $this->assertEquals(array(), $this->enhancer->enhance($defaults, $this->request));
    }

    public function testNotFoundInMapping()
    {
        $defaults = array('type' => 'unknown_route');
        $this->assertEquals($defaults, $this->enhancer->enhance($defaults, $this->request));
    }
}
