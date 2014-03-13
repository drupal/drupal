<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityLabelTest.
 */

namespace Drupal\system\Tests\Entity;

/**
 * Tests entity properties.
 */
class EntityLabelTest extends EntityUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Entity label',
      'description' => 'Tests entity labels.',
      'group' => 'Entity API',
    );
  }

  /**
   * Tests label key and label callback of an entity.
   */
  function testEntityLabel() {
    $entity_types = array(
      'entity_test_no_label',
      'entity_test_label',
      'entity_test_label_callback',
    );

    $values = array(
      'name' => $this->randomName(),
    );
    foreach ($entity_types as $entity_type) {
      $entity = entity_create($entity_type, $values);
      $label = $entity->label();

      switch ($entity_type) {
        case 'entity_test_no_label':
          $this->assertFalse($label, 'Entity with no label property or callback returned FALSE.');
          break;

        case 'entity_test_label':
          $this->assertEqual($label, $entity->name->value, 'Entity with label key returned correct label.');
          break;

        case 'entity_test_label_callback':
          $this->assertEqual($label, 'label callback ' . $entity->name->value, 'Entity with label callback returned correct label.');
          break;
      }
    }
  }
}
