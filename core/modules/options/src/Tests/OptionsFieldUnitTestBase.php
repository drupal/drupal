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
   * The field definition used to created the field entity.
   *
   * @var array
   */
  protected $fieldDefinition;

  /**
   * The list field used in the test.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * The list field instance used in the test.
   *
   * @var \Drupal\field\Entity\FieldInstanceConfig
   */
  protected $instance;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('system', array('router'));

    $this->fieldDefinition = array(
      'name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => 'list_integer',
      'cardinality' => 1,
      'settings' => array(
        'allowed_values' => array(1 => 'One', 2 => 'Two', 3 => 'Three'),
      ),
    );
    $this->field = entity_create('field_config', $this->fieldDefinition);
    $this->field->save();

    $instance = array(
      'field' => $this->field,
      'bundle' => 'entity_test',
    );
    $this->instance = entity_create('field_instance_config', $instance);
    $this->instance->save();

    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($this->fieldName, array(
        'type' => 'options_buttons',
      ))
      ->save();
  }

}
