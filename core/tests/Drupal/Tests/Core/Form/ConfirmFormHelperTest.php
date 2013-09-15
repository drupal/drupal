<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Form\ConfirmFormHelperTest.
 */

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the confirm form helper class.
 *
 * @see \Drupal\Core\Form\ConfirmFormHelper
 *
 * @group Drupal
 * @group Form
 */
class ConfirmFormHelperTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Confirm form helper test',
      'description' => 'Tests the confirm form helper class.',
      'group' => 'Form API',
    );
  }

  /**
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
   * Tests a cancel link route.
   */
  public function testCancelLinkRoute() {
    $cancel_route = array(
      'route_name' => 'foo_bar',
    );
    $form = $this->getMock('Drupal\Core\Form\ConfirmFormInterface');
    $form->expects($this->any())
      ->method('getCancelRoute')
      ->will($this->returnValue($cancel_route));
    $link = ConfirmFormHelper::buildCancelLink($form, new Request());
    $this->assertSame($cancel_route['route_name'], $link['#route_name']);
  }

  /**
   * Tests a cancel link route with parameters.
   */
  public function testCancelLinkRouteWithParams() {
    $cancel_route = array(
      'route_name' => 'foo_bar/{baz}',
      'route_parameters' => array(
        'baz' => 'banana',
      ),
      'options' => array(
        'html' => TRUE,
      ),
    );
    $form = $this->getMock('Drupal\Core\Form\ConfirmFormInterface');
    $form->expects($this->any())
      ->method('getCancelRoute')
      ->will($this->returnValue($cancel_route));
    $link = ConfirmFormHelper::buildCancelLink($form, new Request());
    $this->assertSame($cancel_route['route_name'], $link['#route_name']);
    $this->assertSame($cancel_route['route_parameters'], $link['#route_parameters']);
    $this->assertSame($cancel_route['options'], $link['#options']);
  }

  /**
   * Tests an invalid cancel link route.
   *
   * @expectedException \UnexpectedValueException
   */
  public function testCancelLinkInvalidRoute() {
    $form = $this->getMock('Drupal\Core\Form\ConfirmFormInterface');
    $form->expects($this->any())
      ->method('getCancelRoute')
      ->will($this->returnValue(array('invalid' => 'foo_bar')));
    ConfirmFormHelper::buildCancelLink($form, new Request());
  }

  /**
   * Tests a cancel link provided by the destination.
   */
  public function testCancelLinkDestination() {
    $query = array('destination' => 'baz');
    $form = $this->getMock('Drupal\Core\Form\ConfirmFormInterface');
    $link = ConfirmFormHelper::buildCancelLink($form, new Request($query));
    $this->assertSame($query['destination'], $link['#href']);
  }

}
