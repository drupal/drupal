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

use Symfony\Cmf\Component\Routing\Enhancer\FieldPresenceEnhancer;

use Symfony\Cmf\Component\Routing\Test\CmfUnitTestCase;

class FieldPresenceEnhancerTest extends CmfUnitTestCase
{
    /**
     * @var FieldPresenceEnhancer
     */
    private $mapper;
    private $request;

    public function setUp()
    {
        $this->mapper = new FieldPresenceEnhancer('_template', '_controller', 'cmf_content.controller:indexAction');

        $this->request = Request::create('/test');
    }

    public function testHasTemplate()
    {
        $defaults = array('_template' => 'Bundle:Topic:template.html.twig');
        $expected = array(
            '_template' => 'Bundle:Topic:template.html.twig',
            '_controller' => 'cmf_content.controller:indexAction',
        );
        $this->assertEquals($expected, $this->mapper->enhance($defaults, $this->request));
    }

    public function testFieldAlreadyThere()
    {
        $defaults = array(
            '_template' => 'Bundle:Topic:template.html.twig',
            '_controller' => 'custom.controller:indexAction',
        );
        $this->assertEquals($defaults, $this->mapper->enhance($defaults, $this->request));
    }

    public function testHasNoSourceValue()
    {
        $defaults = array('foo' => 'bar');
        $this->assertEquals($defaults, $this->mapper->enhance($defaults, $this->request));
    }

    public function testHasNoSource()
    {
        $this->mapper = new FieldPresenceEnhancer(null, '_controller', 'cmf_content.controller:indexAction');

        $defaults = array('foo' => 'bar');
        $expected = array(
            'foo' => 'bar',
            '_controller' => 'cmf_content.controller:indexAction',
        );
        $this->assertEquals($expected, $this->mapper->enhance($defaults, $this->request));
    }
}
