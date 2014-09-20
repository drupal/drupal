<?php

/**
 * @file
 * Contains \Drupal\options\Tests\OptionsFieldUnitTestBase.
 */


namespace Drupal\options\Tests;

use Drupal\field\Tests\FieldUnitTestBase;

/**
 * Base class for Options module integration tests.
 */
abstract class OptionsFieldUnitTestBase extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('options');

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'test_options';

  /**
   * The field storage definition used to created the field storage.
   *
   * @var array
   */
  protected $fieldStorageDefinition;

  /**
   * The list field storage used in the test.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The list field used in the test.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', array('router'));

    $this->fieldStorageDefinition = array(
      'name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => 'list_integer',
      'cardinality' => 1,
      'settings' => array(
        'allowed_values' => array(1 => 'One', 2 => 'Two', 3 => 'Three'),
      ),
    );
    $this->fieldStorage = entity_create('field_storage_config', $this->fieldStorageDefinition);
    $this->fieldStorage->save();

    $this->field = entity_create('field_config', array(
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
    ));
    $this->field->save();

    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($this->fieldName, array(
        'type' => 'options_buttons',
      ))
      ->save();
  }

}
