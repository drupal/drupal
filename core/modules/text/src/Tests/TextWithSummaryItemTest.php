<?php

/**
 * @file
 * Contains \Drupal\text\Tests\TextSummaryItemTest.
 */

namespace Drupal\text\Tests;

use Drupal\Core\Language\Language;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\field\Tests\FieldUnitTestBase;

/**
 * Tests for \Drupal\text\Plugin\Field\FieldType\TextWithSummaryItem.
 */
class TextWithSummaryItemTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter');

  /**
   * Field entity.
   *
   * @var \Drupal\field\Entity\FieldConfig.
   */
  protected $field;

  /**
   * Field instance.
   *
   * @var \Drupal\field\Entity\FieldInstanceConfig
   */
  protected $instance;


  public static function getInfo() {
    return array(
      'name' => 'Text summary field item',
      'description' => 'Tests using entity fields of the text summary field type.',
      'group' => 'Field types',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->installSchema('entity_test', array('entity_test_rev', 'entity_test_rev_revision'));

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
    $entity->summary_field->value = $value = $this->randomName();
    $entity->summary_field->summary = $summary = $this->randomName();
    $entity->summary_field->format = NULL;
    $entity->name->value = $this->randomName();
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
   * Tests that the processed values are cached.
   */
  function testProcessedCache() {
    // Use an entity type that has caching enabled.
    $entity_type = 'entity_test_rev';

    $this->createField($entity_type);

    // Create an entity with a summary and a text format.
    $entity = entity_create($entity_type);
    $entity->summary_field->value = $value = $this->randomName();
    $entity->summary_field->summary = $summary = $this->randomName();
    $entity->summary_field->format = 'plain_text';
    $entity->name->value = $this->randomName();
    $entity->save();

    // Check that the processed values are correctly computed.
    $this->assertEqual($entity->summary_field->processed, $value);
    $this->assertEqual($entity->summary_field->summary_processed, $summary);

    // Load the entity and check that the field cache contains the expected
    // data.
    $entity = entity_load($entity_type, $entity->id());
    $cache = \Drupal::cache('entity')->get("field:$entity_type:" . $entity->id());
    $this->assertEqual($cache->data, array(
      Language::LANGCODE_NOT_SPECIFIED => array(
        'summary_field' => array(
          0 => array(
            'value' => $value,
            'summary' => $summary,
            'format' => 'plain_text',
            'processed' => $value,
            'summary_processed' => $summary,
          ),
        ),
      ),
    ));

    // Inject fake processed values into the cache to make sure that these are
    // used as-is and not re-calculated when the entity is loaded.
    $data = array(
      Language::LANGCODE_NOT_SPECIFIED => array(
        'summary_field' => array(
          0 => array(
            'value' => $value,
            'summary' => $summary,
            'format' => 'plain_text',
            'processed' => 'Cached processed value',
            'summary_processed' => 'Cached summary processed value',
          ),
        ),
      ),
    );
    \Drupal::cache('entity')->set("field:$entity_type:" . $entity->id(), $data);
    $entity = entity_load($entity_type, $entity->id(), TRUE);
    $this->assertEqual($entity->summary_field->processed, 'Cached processed value');
    $this->assertEqual($entity->summary_field->summary_processed, 'Cached summary processed value');

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
    $this->field = entity_create('field_config', array(
      'name' => 'summary_field',
      'entity_type' => $entity_type,
      'type' => 'text_with_summary',
      'settings' => array(
        'max_length' => 10,
      )
    ));
    $this->field->save();
    $this->instance = entity_create('field_instance_config', array(
      'field_name' => $this->field->name,
      'entity_type' => $entity_type,
      'bundle' => $entity_type,
      'settings' => array(
        'text_processing' => 0,
      )
    ));
    $this->instance->save();
  }

}
