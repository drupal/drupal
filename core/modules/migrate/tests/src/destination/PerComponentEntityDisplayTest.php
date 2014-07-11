<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\destination\EntityDisplayTest.
 */

namespace Drupal\migrate\Tests\destination;

use Drupal\migrate\Plugin\migrate\destination\ComponentEntityDisplayBase;
use Drupal\migrate\Row;
use Drupal\migrate\Tests\MigrateTestCase;

/**
 * Tests the entity display destination plugin.
 *
 * @group migrate
 */
class PerComponentEntityDisplayTest extends MigrateTestCase {

  /**
   * Tests the entity display import method.
   */
  public function testImport() {
    $values = array(
      'entity_type' => 'entity_type_test',
      'bundle' => 'bundle_test',
      'view_mode' => 'view_mode_test',
      'field_name' => 'field_name_test',
      'options' => array('test setting'),
    );
    $row = new Row(array(), array());
    foreach ($values as $key => $value) {
      $row->setDestinationProperty($key, $value);
    }
    $entity = $this->getMockBuilder('Drupal\entity\Entity\EntityViewDisplay')
      ->disableOriginalConstructor()
      ->getMock();
    $entity->expects($this->once())
      ->method('setComponent')
      ->with('field_name_test', array('test setting'))
      ->will($this->returnSelf());
    $entity->expects($this->once())
      ->method('save')
      ->with();
    $plugin = new TestPerComponentEntityDisplay($entity);
    $this->assertSame($plugin->import($row), array('entity_type_test', 'bundle_test', 'view_mode_test', 'field_name_test'));
    $this->assertSame($plugin->getTestValues(), array('entity_type_test', 'bundle_test', 'view_mode_test'));
  }

}

class TestPerComponentEntityDisplay extends ComponentEntityDisplayBase {
  const MODE_NAME = 'view_mode';
  protected $testValues;
  public function __construct($entity) {
    $this->entity = $entity;
  }
  protected function getEntity($entity_type, $bundle, $view_mode) {
    $this->testValues = func_get_args();
    return $this->entity;
  }
  public function getTestValues() {
    return $this->testValues;
  }
}
