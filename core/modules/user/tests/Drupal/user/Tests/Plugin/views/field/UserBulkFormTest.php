<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Plugin\views\field\UserBulkFormTest.
 */

namespace Drupal\user\Tests\Plugin\views\field;

use Drupal\Tests\UnitTestCase;
use Drupal\user\Plugin\views\field\UserBulkForm;

/**
 * Tests the user bulk form plugin.
 *
 * @see \Drupal\user\Plugin\views\field\UserBulkForm
 */
class UserBulkFormTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'User: Bulk form',
      'description' => 'Tests the user bulk form plugin.',
      'group' => 'Views module integration',
    );
  }

  /**
   * Tests the constructor assignment of actions.
   */
  public function testConstructor() {
    $actions = array();

    for ($i = 1; $i <= 2; $i++) {
      $action = $this->getMockBuilder('Drupal\system\Entity\Action')
        ->disableOriginalConstructor()
        ->getMock();
      $action->expects($this->any())
        ->method('getType')
        ->will($this->returnValue('user'));
      $actions[$i] = $action;
    }

    $action = $this->getMockBuilder('Drupal\system\Entity\Action')
      ->disableOriginalConstructor()
      ->getMock();
    $action->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('node'));
    $actions[] = $action;

    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();
    $storage_controller = $this->getMock('Drupal\Core\Entity\EntityStorageControllerInterface');
    $storage_controller->expects($this->any())
      ->method('loadMultiple')
      ->will($this->returnValue($actions));

    $entity_manager->expects($this->any())
      ->method('getStorageController')
      ->with('action')
      ->will($this->returnValue($storage_controller));

    $user_bulk_form = new UserBulkForm(array(), 'user_bulk_form', array(), $entity_manager);

    $this->assertAttributeEquals(array_slice($actions, 0, -1, TRUE), 'actions', $user_bulk_form);
  }

}
