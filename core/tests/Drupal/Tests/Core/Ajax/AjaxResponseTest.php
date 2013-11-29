<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Ajax\AjaxResponseTest.
 */

namespace Drupal\Tests\Core\Ajax;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the AJAX response object.
 *
 * @see \Drupal\Core\Ajax\AjaxResponse
 */
class AjaxResponseTest extends UnitTestCase {

  /**
   * The tested ajax response object.
   *
   * @var \Drupal\Core\Ajax\AjaxResponse
   */
  protected $ajaxResponse;

  public static function getInfo() {
    return array(
      'name' => 'Ajax Response Object',
      'description' => 'Tests the AJAX response object.',
      'group' => 'Ajax',
    );
  }

  protected function setUp() {
    $this->ajaxResponse = new AjaxResponse();
  }

  /**
   * Tests the add and getCommands method.
   *
   * @see \Drupal\Core\Ajax\AjaxResponse::addCommand()
   * @see \Drupal\Core\Ajax\AjaxResponse::getCommands()
   */
  public function testCommands() {
    $command_one = $this->getMock('Drupal\Core\Ajax\CommandInterface');
    $command_one->expects($this->once())
      ->method('render')
      ->will($this->returnValue(array('command' => 'one')));
    $command_two = $this->getMock('Drupal\Core\Ajax\CommandInterface');
    $command_two->expects($this->once())
      ->method('render')
      ->will($this->returnValue(array('command' => 'two')));
    $command_three = $this->getMock('Drupal\Core\Ajax\CommandInterface');
    $command_three->expects($this->once())
      ->method('render')
      ->will($this->returnValue(array('command' => 'three')));

    $this->ajaxResponse->addCommand($command_one);
    $this->ajaxResponse->addCommand($command_two);
    $this->ajaxResponse->addCommand($command_three, TRUE);

    // Ensure that the added commands are in the right order.
    $commands =& $this->ajaxResponse->getCommands();
    $this->assertSame($commands[1], array('command' => 'one'));
    $this->assertSame($commands[2], array('command' => 'two'));
    $this->assertSame($commands[0], array('command' => 'three'));

    // Remove one and change one element from commands and ensure the reference
    // worked as expected.
    unset($commands[2]);
    $commands[0]['class'] = 'test-class';

    $commands = $this->ajaxResponse->getCommands();
    $this->assertSame($commands[1], array('command' => 'one'));
    $this->assertFalse(isset($commands[2]));
    $this->assertSame($commands[0], array('command' => 'three', 'class' => 'test-class'));
  }


}
