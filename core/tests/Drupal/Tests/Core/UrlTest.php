<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\UrlTest.
 */

namespace Drupal\Tests\Core;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Route;

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
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pathAliasManager;

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

    $alias_map = array(
      // Set up one proper alias that can be resolved to a system path.
      array('node-alias-test', NULL, 'node'),
      // Passing in anything else should return the same string.
      array('node', NULL, 'node'),
      array('node/1', NULL, 'node/1'),
      array('node/2/edit', NULL, 'node/2/edit'),
      array('non-existent', NULL, 'non-existent'),
    );

    $this->urlGenerator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $this->urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->will($this->returnValueMap($this->map));

    $this->pathAliasManager = $this->getMock('Drupal\Core\Path\AliasManagerInterface');
    $this->pathAliasManager->expects($this->any())
      ->method('getPathByAlias')
      ->will($this->returnValueMap($alias_map));

    $this->router = $this->getMock('Drupal\Tests\Core\Routing\TestRouterInterface');
    $this->container = new ContainerBuilder();
    $this->container->set('router.no_access_checks', $this->router);
    $this->container->set('url_generator', $this->urlGenerator);
    $this->container->set('path.alias_manager', $this->pathAliasManager);
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
      $expected = Url::fromRoute($values[0], $values[1], $values[2]);
      $this->assertEquals($expected, $url);
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
   * Tests the getInternalPath method().
   *
   * @param \Drupal\Core\Url[] $urls
   *   Array of URL objects.
   *
   * @covers ::getInternalPath
   *
   * @depends testUrlFromRequest
   */
  public function testGetInternalPath($urls) {
    $map = [];
    $map[] = ['view.frontpage.page_1', [], '/node'];
    $map[] = ['node_view', ['node' => '1'], '/node/1'];
    $map[] = ['node_edit', ['node' => '2'], '/node/2/edit'];

    foreach ($urls as $index => $url) {
      // Clone the url so that there is no leak of internal state into the
      // other ones.
      $url = clone $url;
      $url_generator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
      $url_generator->expects($this->once())
        ->method('getPathFromRoute')
        ->will($this->returnValueMap($map, $index));
      $url->setUrlGenerator($url_generator);

      $url->getInternalPath();
      $url->getInternalPath();
    }
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
   * Tests the __toString() method.
   *
   * @param \Drupal\Core\Url[] $urls
   *   An array of Url objects.
   *
   * @depends testUrlFromRequest
   *
   * @covers ::__toString
   */
  public function testMagicToString($urls) {
    foreach ($urls as $index => $url) {
      $url->setUrlGenerator(\Drupal::urlGenerator());
      $path = array_pop($this->map[$index]);
      $this->assertSame($path, (string) $url);
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
      $expected = Url::fromRoute($this->map[$index][0], $this->map[$index][1], $this->map[$index][2]);
      $expected->setUrlGenerator(\Drupal::urlGenerator());
      $this->assertEquals($expected, $url);
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
      '#url' => Url::fromRoute('entity.node.canonical', ['node' => 3]),
    );
    $this->container->set('current_user', $this->getMock('Drupal\Core\Session\AccountInterface'));
    $this->container->set('access_manager', $this->getMockAccessManager($access));
    $this->assertEquals($access, TestUrl::renderAccess($element));
  }

  /**
   * Tests the fromRouteMatch() method.
   */
  public function testFromRouteMatch() {
    $route = new Route('/test-route/{foo}');
    $route_match = new RouteMatch('test_route', $route, ['foo' => (object) [1]], ['foo' => 1]);
    $url = Url::fromRouteMatch($route_match);
    $this->assertSame('test_route', $url->getRouteName());
    $this->assertEquals(['foo' => '1'] , $url->getRouteParameters());
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
