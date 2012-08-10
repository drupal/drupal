<?php

/**
 * @file
 * Definition of Drupal\field\Tests\EntityPropertiesTest.
 */

namespace Drupal\field\Tests;

/**
 * Tests entity properties.
 */
class EntityPropertiesTest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test');

  public static function getInfo() {
    return array(
      'name' => 'Entity properties',
      'description' => 'Tests entity properties.',
      'group' => 'Entity API',
    );
  }

  /**
   * Tests label key and label callback of an entity.
   */
  function testEntityLabel() {
    $entity_types = array(
      'test_entity_no_label',
      'test_entity_label',
      'test_entity_label_callback',
    );

    // @todo Remove once test_entity entity has been merged with entity_test.
    $values = array(
      'ftlabel' => $this->randomName(),
    );

    foreach ($entity_types as $entity_type) {
      $entity = entity_create($entity_type, $values);
      $label = $entity->label();

      switch ($entity_type) {
        case 'test_entity_no_label':
          $this->assertFalse($label, 'Entity with no label property or callback returned FALSE.');
          break;

        case 'test_entity_label':
          $this->assertEqual($label, $entity->ftlabel, 'Entity with label key returned correct label.');
          break;

        case 'test_entity_label_callback':
          $this->assertEqual($label, 'label callback ' . $entity->ftlabel, 'Entity with label callback returned correct label.');
          break;
      }
    }
  }
}
