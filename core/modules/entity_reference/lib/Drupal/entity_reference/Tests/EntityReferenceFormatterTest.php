<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceFormatterTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\system\Tests\Entity\EntityUnitTestBase;

use Symfony\Component\HttpFoundation\Request;

/**
 * Tests Entity Reference formatters.
 */
class EntityReferenceFormatterTest extends EntityUnitTestBase {

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * The name of the field used in this test.
   *
   * @var string
   */
  protected $fieldName = 'field_test';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_reference');

  public static function getInfo() {
    return array(
      'name' => 'Entity reference formatters',
      'description' => 'Tests the formatters functionality.',
      'group' => 'Entity Reference',
    );
  }

  public function setUp() {
    parent::setUp();

    entity_reference_create_instance($this->entityType, $this->bundle, $this->fieldName, 'Field test', $this->entityType);
  }

  /**
   * Assert unaccessible items don't change the data of the fields.
   */
  public function testAccess() {
    $field_name = $this->fieldName;

    $entity_1 = entity_create($this->entityType, array('name' => $this->randomName()));
    $entity_1->save();

    $entity_2 = entity_create($this->entityType, array('name' => $this->randomName()));
    $entity_2->save();
    $entity_2->{$field_name}->entity = $entity_1;

    // Assert user doesn't have access to the entity.
    $this->assertFalse($entity_1->access('view'), 'Current user does not have access to view the referenced entity.');

    $formatter_manager = $this->container->get('plugin.manager.field.formatter');

    // Get all the existing formatters.
    foreach ($formatter_manager->getOptions('entity_reference') as $formatter => $name) {
      // Set formatter type for the 'full' view mode.
      entity_get_display($this->entityType, $this->bundle, 'default')
        ->setComponent($field_name, array(
          'type' => $formatter,
        ))
        ->save();

      // Invoke entity view.
      entity_view($entity_2, 'default');

      // Verify the un-accessible item still exists.
      $this->assertEqual($entity_2->{$field_name}->value, $entity_1->id(), format_string('The un-accessible item still exists after @name formatter was executed.', array('@name' => $name)));
    }
  }
}
