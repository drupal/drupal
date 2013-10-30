<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Plugin\views\field\NodeBulkFormTest.
 */

namespace Drupal\node\Tests\Plugin\views\field;

use Drupal\node\Plugin\views\field\NodeBulkForm;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the node bulk form plugin.
 *
 * @see \Drupal\node\Plugin\views\field\NodeBulkForm
 */
class NodeBulkFormTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Node: Bulk form',
      'description' => 'Tests the node bulk form plugin.',
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
        ->will($this->returnValue('node'));
      $actions[$i] = $action;
    }

    $action = $this->getMockBuilder('Drupal\system\Entity\Action')
      ->disableOriginalConstructor()
      ->getMock();
    $action->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('user'));
    $actions[] = $action;

    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $storage_controller = $this->getMock('Drupal\Core\Entity\EntityStorageControllerInterface');
    $storage_controller->expects($this->any())
      ->method('loadMultiple')
      ->will($this->returnValue($actions));

    $entity_manager->expects($this->any())
      ->method('getStorageController')
      ->with('action')
      ->will($this->returnValue($storage_controller));
    $node_bulk_form = new NodeBulkForm(array(), 'node_bulk_form', array(), $entity_manager);

    $this->assertAttributeEquals(array_slice($actions, 0, -1, TRUE), 'actions', $node_bulk_form);
  }

}
