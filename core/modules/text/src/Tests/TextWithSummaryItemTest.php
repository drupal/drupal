<?php

/**
 * @file
 * Contains \Drupal\text\Tests\TextSummaryItemTest.
 */

namespace Drupal\text\Tests;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\field\Tests\FieldUnitTestBase;

/**
 * Tests using entity fields of the text summary field type.
 *
 * @group text
 */
class TextWithSummaryItemTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter');

  /**
   * Field storage entity.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig.
   */
  protected $fieldStorage;

  /**
   * Field instance.
   *
   * @var \Drupal\field\Entity\FieldInstanceConfig
   */
  protected $instance;


  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');

    // Create the necessary formats.
    $this->installConfig(array('filter'));
    entity_create('filter_format', array(
      'format' => 'no_filters',
      'filters' => array(),
    ))->save();
  }

  /**
   * Tests processed properties.
   */
  public function testCrudAndUpdate() {
    $entity_type = 'entity_test';
    $this->createField($entity_type);

    // Create an entity with a summary and no text format.
    $entity = entity_create($entity_type);
    $entity->summary_field->value = $value = $this->randomMachineName();
    $entity->summary_field->summary = $summary = $this->randomMachineName();
    $entity->summary_field->format = NULL;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $entity = entity_load($entity_type, $entity->id());
    $this->assertTrue($entity->summary_field instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->summary_field[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->summary_field->value, $value);
    $this->assertEqual($entity->summary_field->processed, $value);
    $this->assertEqual($entity->summary_field->summary, $summary);
    $this->assertEqual($entity->summary_field->summary_processed, $summary);
    $this->assertNull($entity->summary_field->format);

    // Enable text processing.
    $this->instance->settings['text_processing'] = 1;
    $this->instance->save();

    // Re-load the entity.
    $entity = entity_load($entity_type, $entity->id(), TRUE);

    // Even if no format is given, if text processing is enabled, the default
    // format is used.
    $this->assertEqual($entity->summary_field->processed, "<p>$value</p>\n");
    $this->assertEqual($entity->summary_field->summary_processed, "<p>$summary</p>\n");

    // Change the format, this should update the processed properties.
    $entity->summary_field->format = 'no_filters';
    $this->assertEqual($entity->summary_field->processed, $value);
    $this->assertEqual($entity->summary_field->summary_processed, $summary);
  }

  /**
   * Creates a text_with_summary field and field instance.
   *
   * @param string $entity_type
   *   Entity type for which the field should be created.
   */
  protected function createField($entity_type) {
    // Create a field .
    $this->fieldStorage = entity_create('field_storage_config', array(
      'name' => 'summary_field',
      'entity_type' => $entity_type,
      'type' => 'text_with_summary',
      'settings' => array(
        'max_length' => 10,
      )
    ));
    $this->fieldStorage->save();
    $this->instance = entity_create('field_instance_config', array(
      'field_storage' => $this->fieldStorage,
      'bundle' => $entity_type,
      'settings' => array(
        'text_processing' => 0,
      )
    ));
    $this->instance->save();
  }

}
