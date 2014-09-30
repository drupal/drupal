<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\UrlTest.
 */

namespace Drupal\Tests\Core;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * @coversDefaultClass \Drupal\Core\Url
 * @group UrlTest
 */
class UrlTest extends UnitTestCase {

  /**
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

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
    $this->container = new ContainerBuilder();
    $this->container->set('router.no_access_checks', $this->router);
    $this->container->set('url_generator', $this->urlGenerator);
    \Drupal::setContainer($this->container);
  }

  /**
   * Tests creating a Url from a request.
   */
  public function testUrlFromRequest() {
    $this->router->expects($this->at(0))
      ->method('matchRequest')
      ->with($this->getRequestConstraint('/node'))
      ->willReturn([
          RouteObjectInterface::ROUTE_NAME => 'view.frontpage.page_1',
          '_raw_variables' => new ParameterBag(),
        ]);
    $this->router->expects($this->at(1))
      ->method('matchRequest')
      ->with($this->getRequestConstraint('/node/1'))
      ->willReturn([
        RouteObjectInterface::ROUTE_NAME => 'node_view',
        '_raw_variables' => new ParameterBag(['node' => '1']),
      ]);
    $this->router->expects($this->at(2))
      ->method('matchRequest')
      ->with($this->getRequestConstraint('/node/2/edit'))
      ->willReturn([
        RouteObjectInterface::ROUTE_NAME => 'node_edit',
        '_raw_variables' => new ParameterBag(['node' => '2']),
      ]);

    $urls = array();
    foreach ($this->map as $index => $values) {
      $path = array_pop($values);
      $url = Url::createFromRequest(Request::create("$path"));
      $this->assertSame($values, array_values($url->toArray()));
      $urls[$index] = $url;
    }
    return $urls;
  }

   /**
   * This constraint checks whether a Request object has the right path.
   *
   * @param string $path
   *   The path.
   *
   * @return \PHPUnit_Framework_Constraint_Callback
   *   The constraint checks whether a Request object has the right path.
   */
  protected function getRequestConstraint($path) {
    return $this->callback(function (Request $request) use ($path) {
      return $request->getPathInfo() == $path;
    });
  }

  /**
   * Tests the fromRoute() method with the special <front> path.
   *
   * @covers ::fromRoute
   */
  public function testFromRouteFront() {
    $url = Url::fromRoute('<front>');
    $this->assertSame('<front>', $url->getRouteName());
  }

  /**
   * Tests the createFromRequest method.
   *
   * @covers ::createFromRequest
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
   * @covers ::createFromRequest
   *
   * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
   */
  public function testUrlFromRequestInvalid() {
    $request = Request::create('/test-path');

    $this->router->expects($this->once())
      ->method('matchRequest')
      ->with($request)
      ->will($this->throwException(new ResourceNotFoundException()));

    $this->assertNull(Url::createFromRequest($request));
  }

  /**
   * Tests the isExternal() method.
   *
   * @depends testUrlFromRequest
   *
   * @covers ::isExternal
   */
  public function testIsExternal($urls) {
    foreach ($urls as $url) {
      $this->assertFalse($url->isExternal());
    }
  }

  /**
   * Tests the getUri() method for internal URLs.
   *
   * @param \Drupal\Core\Url[] $urls
   *   Array of URL objects.
   *
   * @depends testUrlFromRequest
   *
   * @expectedException \UnexpectedValueException
   *
   * @covers ::getUri
   */
  public function testGetUriForInternalUrl($urls) {
    foreach ($urls as $url) {
      $url->getUri();
    }
  }

  /**
   * Tests the getUri() method for external URLs.
   *
   * @covers ::getUri
   */
  public function testGetUriForExternalUrl() {
    $url = Url::fromUri('http://example.com/test');
    $this->assertEquals('http://example.com/test', $url->getUri());
  }

  /**
   * Tests the toString() method.
   *
   * @param \Drupal\Core\Url[] $urls
   *   An array of Url objects.
   *
   * @depends testUrlFromRequest
   *
   * @covers ::toString
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
   * @depends testUrlFromRequest
   *
   * @covers ::toArray
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
   * @depends testUrlFromRequest
   *
   * @covers ::getRouteName
   */
  public function testGetRouteName($urls) {
    foreach ($urls as $index => $url) {
      $this->assertSame($this->map[$index][0], $url->getRouteName());
    }
  }

  /**
   * Tests the getRouteName() with an external URL.
   *
   * @covers ::getRouteName
   * @expectedException \UnexpectedValueException
   */
  public function testGetRouteNameWithExternalUrl() {
    $url = Url::fromUri('http://example.com');
    $url->getRouteName();
  }

  /**
   * Tests the getRouteParameters() method.
   *
   * @param \Drupal\Core\Url[] $urls
   *   An array of Url objects.
   *
   * @depends testUrlFromRequest
   *
   * @covers ::getRouteParameters
   */
  public function testGetRouteParameters($urls) {
    foreach ($urls as $index => $url) {
      $this->assertSame($this->map[$index][1], $url->getRouteParameters());
    }
  }

  /**
   * Tests the getRouteParameters() with an external URL.
   *
   * @covers ::getRouteParameters
   * @expectedException \UnexpectedValueException
   */
  public function testGetRouteParametersWithExternalUrl() {
    $url = Url::fromUri('http://example.com');
    $url->getRouteParameters();
  }

  /**
   * Tests the getOptions() method.
   *
   * @param \Drupal\Core\Url[] $urls
   *   An array of Url objects.
   *
   * @depends testUrlFromRequest
   *
   * @covers ::getOptions
   */
  public function testGetOptions($urls) {
    foreach ($urls as $index => $url) {
      $this->assertSame($this->map[$index][2], $url->getOptions());
    }
  }

  /**
   * Tests the access() method.
   *
   * @param bool $access
   *
   * @covers ::access
   * @covers ::accessManager
   * @dataProvider accessProvider
   */
  public function testAccess($access) {
    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $url = new TestUrl('entity.node.canonical', ['node' => 3]);
    $url->setAccessManager($this->getMockAccessManager($access, $account));
    $this->assertEquals($access, $url->access($account));
  }

  /**
   * Tests the renderAccess() method.
   *
   * @param bool $access
   *
   * @covers ::renderAccess
   * @dataProvider accessProvider
   */
  public function testRenderAccess($access) {
    $element = array(
      '#route_name' => 'entity.node.canonical',
      '#route_parameters' => ['node' => 3],
      '#options' => [],
    );
    $this->container->set('current_user', $this->getMock('Drupal\Core\Session\AccountInterface'));
    $this->container->set('access_manager', $this->getMockAccessManager($access));
    $this->assertEquals($access, TestUrl::renderAccess($element));
  }

  /**
   * Creates a mock access manager for the access tests.
   *
   * @param bool $access
   * @param \Drupal\Core\Session\AccountInterface|NULL $account
   *
   * @return \Drupal\Core\Access\AccessManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected function getMockAccessManager($access, $account = NULL) {
    $access_manager = $this->getMock('Drupal\Core\Access\AccessManagerInterface');
    $access_manager->expects($this->once())
      ->method('checkNamedRoute')
      ->with('entity.node.canonical', ['node' => 3], $account)
      ->willReturn($access);
    return $access_manager;
  }

  /**
   * Data provider for the access test methods.
   */
  public function accessProvider() {
    return array(
      array(TRUE),
      array(FALSE),
    );
  }

}

class TestUrl extends Url {

  /**
   * Sets the access manager.
   *
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   */
  public function setAccessManager(AccessManagerInterface $access_manager) {
    $this->accessManager = $access_manager;
  }


}
