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
  public static function getInfo() {
    return array(
      'name' => 'Entity properties',
      'description' => 'Tests entity properties.',
      'group' => 'Entity API',
    );
  }

  function setUp() {
    parent::setUp('field_test');
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

    $entity = field_test_create_stub_entity();

    foreach ($entity_types as $entity_type) {
      $label = entity_label($entity_type, $entity);

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
