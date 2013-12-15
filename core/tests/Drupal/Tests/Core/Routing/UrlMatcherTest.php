<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Routing\UrlMatcherTest.
 */

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\UrlMatcher;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Tests the menu link entity class.
 *
 * @group Drupal
 * @group Routing
 *
 * @see \Drupal\Core\Routing\UrlMatcher
 */
class UrlMatcherTest extends UnitTestCase {

  /**
   * The url generator to test.
   *
   * @var \Drupal\Core\Routing\UrlMatcher
   */
  protected $matcher;

  public static function getInfo() {
    return array(
      'name' => 'UrlMatcher',
      'description' => 'Confirm that the UrlMatcher is functioning properly.',
      'group' => 'Routing',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->matcher = new UrlMatcher();
  }

  /**
   * Tests the findRouteNameParameters method.
   *
   * @see \Drupal\Core\Routing\UrlMatcher::findRouteNameParameters()
   */
  public function testFindRouteNameParameters() {
    $router = $this->getMock('Symfony\Component\Routing\Matcher\RequestMatcherInterface');
    $container = new ContainerBuilder();
    $container->set('router', $router);
    \Drupal::setContainer($container);

    $router->expects($this->at(0))
      ->method('matchRequest')
      ->will($this->returnValue(array(
        RouteObjectInterface::ROUTE_NAME => 'view.frontpage.page_1',
        '_raw_variables' => new ParameterBag(),
      )));
    $router->expects($this->at(1))
      ->method('matchRequest')
      ->will($this->returnValue(array(
        RouteObjectInterface::ROUTE_NAME => 'node_view',
        '_raw_variables' => new ParameterBag(array('node' => '1')),
      )));
    $router->expects($this->at(2))
      ->method('matchRequest')
      ->will($this->returnValue(array(
        RouteObjectInterface::ROUTE_NAME => 'node_edit',
        '_raw_variables' => new ParameterBag(array('node' => '2')),
      )));
    $router->expects($this->at(3))
      ->method('matchRequest')
      ->will($this->throwException(new ResourceNotFoundException()));

    $this->assertEquals(array('view.frontpage.page_1', array()), $this->matcher->findRouteNameParameters('node'));
    $this->assertEquals(array('node_view', array('node' => '1')), $this->matcher->findRouteNameParameters('node/1'));
    $this->assertEquals(array('node_edit', array('node' => '2')), $this->matcher->findRouteNameParameters('node/2/edit'));

    $this->assertEquals(array(), $this->matcher->findRouteNameParameters('non-existing'));
  }

}
