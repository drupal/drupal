<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Form\ConfirmFormHelperTest.
 */

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\Core\Form\ConfirmFormHelper
 * @group Form
 */
class ConfirmFormHelperTest extends UnitTestCase {

  /**
   * @covers ::buildCancelLink
   *
   * Tests the cancel link title.
   */
  public function testCancelLinkTitle() {
    $cancel_text = 'Cancel text';
    $form = $this->getMock('Drupal\Core\Form\ConfirmFormInterface');
    $form->expects($this->any())
      ->method('getCancelText')
      ->will($this->returnValue($cancel_text));

    $link = ConfirmFormHelper::buildCancelLink($form, new Request());
    $this->assertSame($cancel_text, $link['#title']);
  }

  /**
   * @covers ::buildCancelLink
   *
   * Tests a cancel link route.
   */
  public function testCancelLinkRoute() {
    $route_name = 'foo_bar';
    $cancel_route = new Url($route_name);
    $form = $this->getMock('Drupal\Core\Form\ConfirmFormInterface');
    $form->expects($this->any())
      ->method('getCancelUrl')
      ->will($this->returnValue($cancel_route));
    $link = ConfirmFormHelper::buildCancelLink($form, new Request());
    $this->assertSame($route_name, $link['#route_name']);
  }

  /**
   * @covers ::buildCancelLink
   *
   * Tests a cancel link route with parameters.
   */
  public function testCancelLinkRouteWithParams() {
    $cancel_route = array(
      'route_name' => 'foo_bar.baz',
      'route_parameters' => array(
        'baz' => 'banana',
      ),
      'options' => array(
        'absolute' => TRUE,
      ),
    );
    $form = $this->getMock('Drupal\Core\Form\ConfirmFormInterface');
    $form->expects($this->any())
      ->method('getCancelUrl')
      ->will($this->returnValue(new Url($cancel_route['route_name'], $cancel_route['route_parameters'], $cancel_route['options'])));
    $link = ConfirmFormHelper::buildCancelLink($form, new Request());
    $this->assertSame($cancel_route['route_name'], $link['#route_name']);
    $this->assertSame($cancel_route['route_parameters'], $link['#route_parameters']);
    $this->assertSame($cancel_route['options'], $link['#options']);
  }

  /**
   * @covers ::buildCancelLink
   *
   * Tests a cancel link route with a URL object.
   */
  public function testCancelLinkRouteWithUrl() {
    $cancel_route = new Url(
      'foo_bar.baz', array(
        'baz' => 'banana',
      ),
      array(
        'absolute' => TRUE,
      )
    );
    $form = $this->getMock('Drupal\Core\Form\ConfirmFormInterface');
    $form->expects($this->any())
      ->method('getCancelUrl')
      ->will($this->returnValue($cancel_route));
    $link = ConfirmFormHelper::buildCancelLink($form, new Request());
    $this->assertSame($cancel_route->getRouteName(), $link['#route_name']);
    $this->assertSame($cancel_route->getRouteParameters(), $link['#route_parameters']);
    $this->assertSame($cancel_route->getOptions(), $link['#options']);
  }

  /**
   * @covers ::buildCancelLink
   *
   * Tests a cancel link provided by the destination.
   */
  public function testCancelLinkDestination() {
    $query = array('destination' => 'baz');
    $form = $this->getMock('Drupal\Core\Form\ConfirmFormInterface');
    $link = ConfirmFormHelper::buildCancelLink($form, new Request($query));
    $this->assertSame($query['destination'], $link['#href']);
  }

}
