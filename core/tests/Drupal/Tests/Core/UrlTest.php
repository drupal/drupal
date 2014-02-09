<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\UrlTest.
 */

namespace Drupal\Tests\Core;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Tests the \Drupal\Core\Url class.
 *
 * @group Drupal
 * @group Url
 *
 * @coversDefaultClass \Drupal\Core\Url
 */
class UrlTest extends UnitTestCase {

  /**
   * The URL generator
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlGenerator;

  /**
   * The router.
   *
   * @var \Drupal\Tests\Core\Routing\TestRouterInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $router;

  /**
   * An array of values to use for the test.
   *
   * @var array
   */
  protected $map;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Url object (internal)',
      'description' => 'Tests the \Drupal\Core\Url class.',
      'group' => 'Routing',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $map = array();
    $map[] = array('view.frontpage.page_1', array(), array(), '/node');
    $map[] = array('node_view', array('node' => '1'), array(), '/node/1');
    $map[] = array('node_edit', array('node' => '2'), array(), '/node/2/edit');
    $this->map = $map;

    $this->urlGenerator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $this->urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->will($this->returnValueMap($this->map));

    $this->router = $this->getMock('Drupal\Tests\Core\Routing\TestRouterInterface');
    $container = new ContainerBuilder();
    $container->set('router', $this->router);
    $container->set('url_generator', $this->urlGenerator);
    \Drupal::setContainer($container);
  }

  /**
   * Tests the createFromPath method.
   *
   * @covers ::createFromPath()
   */
  public function testCreateFromPath() {
    $this->router->expects($this->any())
      ->method('match')
      ->will($this->returnValueMap(array(
        array('/node', array(
          RouteObjectInterface::ROUTE_NAME => 'view.frontpage.page_1',
          '_raw_variables' => new ParameterBag(),
        )),
        array('/node/1', array(
          RouteObjectInterface::ROUTE_NAME => 'node_view',
          '_raw_variables' => new ParameterBag(array('node' => '1')),
        )),
        array('/node/2/edit', array(
          RouteObjectInterface::ROUTE_NAME => 'node_edit',
          '_raw_variables' => new ParameterBag(array('node' => '2')),
        )),
      )));

    $urls = array();
    foreach ($this->map as $index => $values) {
      $path = trim(array_pop($values), '/');
      $url = Url::createFromPath($path);
      $this->assertSame($values, array_values($url->toArray()));
      $urls[$index] = $url;
    }
    return $urls;
  }

  /**
   * Tests the createFromPath method with the special <front> path.
   *
   * @covers ::createFromPath()
   */
  public function testCreateFromPathFront() {
    $url = Url::createFromPath('<front>');
    $this->assertSame('<front>', $url->getRouteName());
  }

  /**
   * Tests that an invalid path will thrown an exception.
   *
   * @covers ::createFromPath()
   *
   * @expectedException \Drupal\Core\Routing\MatchingRouteNotFoundException
   * @expectedExceptionMessage No matching route could be found for the path "non-existent"
   */
  public function testCreateFromPathInvalid() {
    $this->router->expects($this->once())
      ->method('match')
      ->with('/non-existent')
      ->will($this->throwException(new ResourceNotFoundException()));

    $this->assertNull(Url::createFromPath('non-existent'));
  }

  /**
   * Tests the createFromRequest method.
   *
   * @covers ::createFromRequest()
   */
  public function testCreateFromRequest() {
    $attributes = array(
      '_raw_variables' => new ParameterBag(array(
        'color' => 'chartreuse',
      )),
      RouteObjectInterface::ROUTE_NAME => 'the_route_name',
    );
    $request = new Request(array(), array(), $attributes);

    $this->router->expects($this->once())
      ->method('matchRequest')
      ->with($request)
      ->will($this->returnValue($attributes));

    $url = Url::createFromRequest($request);
    $expected = new Url('the_route_name', array('color' => 'chartreuse'));
    $this->assertEquals($expected, $url);
  }

  /**
   * Tests that an invalid request will thrown an exception.
   *
   * @covers ::createFromRequest()
   *
   * @expectedException \Drupal\Core\Routing\MatchingRouteNotFoundException
   * @expectedExceptionMessage No matching route could be found for the request: request_as_a_string
   */
  public function testCreateFromRequestInvalid() {
    // Mock the request in order to override the __toString() method.
    $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
    $request->expects($this->once())
      ->method('__toString')
      ->will($this->returnValue('request_as_a_string'));

    $this->router->expects($this->once())
      ->method('matchRequest')
      ->with($request)
      ->will($this->throwException(new ResourceNotFoundException()));

    $this->assertNull(Url::createFromRequest($request));
  }

  /**
   * Tests the isExternal() method.
   *
   * @depends testCreateFromPath
   *
   * @covers ::isExternal()
   */
  public function testIsExternal($urls) {
    foreach ($urls as $url) {
      $this->assertFalse($url->isExternal());
    }
  }

  /**
   * Tests the toString() method.
   *
   * @param \Drupal\Core\Url[] $urls
   *   An array of Url objects.
   *
   * @depends testCreateFromPath
   *
   * @covers ::toString()
   */
  public function testToString($urls) {
    foreach ($urls as $index => $url) {
      $path = array_pop($this->map[$index]);
      $this->assertSame($path, $url->toString());
    }
  }

  /**
   * Tests the toArray() method.
   *
   * @param \Drupal\Core\Url[] $urls
   *   An array of Url objects.
   *
   * @depends testCreateFromPath
   *
   * @covers ::toArray()
   */
  public function testToArray($urls) {
    foreach ($urls as $index => $url) {
      $expected = array(
        'route_name' => $this->map[$index][0],
        'route_parameters' => $this->map[$index][1],
        'options' => $this->map[$index][2],
      );
      $this->assertSame($expected, $url->toArray());
    }
  }

  /**
   * Tests the getRouteName() method.
   *
   * @param \Drupal\Core\Url[] $urls
   *   An array of Url objects.
   *
   * @depends testCreateFromPath
   *
   * @covers ::getRouteName()
   */
  public function testGetRouteName($urls) {
    foreach ($urls as $index => $url) {
      $this->assertSame($this->map[$index][0], $url->getRouteName());
    }
  }

  /**
   * Tests the getRouteParameters() method.
   *
   * @param \Drupal\Core\Url[] $urls
   *   An array of Url objects.
   *
   * @depends testCreateFromPath
   *
   * @covers ::getRouteParameters()
   */
  public function testGetRouteParameters($urls) {
    foreach ($urls as $index => $url) {
      $this->assertSame($this->map[$index][1], $url->getRouteParameters());
    }
  }

  /**
   * Tests the getOptions() method.
   *
   * @param \Drupal\Core\Url[] $urls
   *   An array of Url objects.
   *
   * @depends testCreateFromPath
   *
   * @covers ::getOptions()
   */
  public function testGetOptions($urls) {
    foreach ($urls as $index => $url) {
      $this->assertSame($this->map[$index][2], $url->getOptions());
    }
  }

}
