<?php

/**
 * @file
 * Contains \Drupal\menu_link\Tests\Plugin\Core\Entity\MenuLinkTest.
 */

namespace Drupal\menu_link\Tests\Plugin\Core\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\menu_link\Entity\MenuLink;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Tests the menu link entity class.
 *
 * @group Drupal
 * @group Drupal_menu
 *
 * @see \Drupal\menu_link\Plugin\Core\Entity\MenuLink
 */
class MenuLinkTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Menu link entity',
      'description' => 'Tests the menu link entity class.',
      'group' => 'Menu',
    );
  }

  /**
   * Tests the findRouteNameParameters method.
   *
   * @see \Drupal\menu_link\Plugin\Core\Entity\MenuLink::findRouteNameParameters()
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

    $this->assertEquals(array('view.frontpage.page_1', array()), MenuLink::findRouteNameParameters('node'));
    $this->assertEquals(array('node_view', array('node' => '1')), MenuLink::findRouteNameParameters('node/1'));
    $this->assertEquals(array('node_edit', array('node' => '2')), MenuLink::findRouteNameParameters('node/2/edit'));

    $this->assertEquals(array(), MenuLink::findRouteNameParameters('non-existing'));
  }

}
