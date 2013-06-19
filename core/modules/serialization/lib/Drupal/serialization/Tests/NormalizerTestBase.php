<?php

/**
 * @file
 *
 */

namespace Drupal\serialization\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

abstract class NormalizerTestBase extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('serialization', 'system', 'entity', 'field', 'entity_test', 'text', 'field_sql_storage');

  protected function setUp() {
    parent::setUp();

    $this->installSchema('entity_test', array('entity_test_mulrev', 'entity_test_mulrev_property_revision', 'entity_test_mulrev_property_data'));
    $this->installSchema('system', array('url_alias'));
    $this->installConfig(array('field'));

    // Auto-create a field for testing.
    entity_create('field_entity', array(
      'field_name' => 'field_test_text',
      'type' => 'text',
      'cardinality' => 1,
      'translatable' => FALSE,
    ))->save();
    entity_create('field_instance', array(
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_text',
      'bundle' => 'entity_test_mulrev',
      'label' => 'Test text-field',
      'widget' => array(
        'type' => 'text_textfield',
        'weight' => 0,
      ),
    ))->save();
  }

}
