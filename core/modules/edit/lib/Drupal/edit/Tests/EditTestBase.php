<?php

/**
 * @file
 * Contains \Drupal\edit\Tests\EditTestBase.
 */

namespace Drupal\edit\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Parent class for Edit tests.
 */
class EditTestBase extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'entity', 'entity_test', 'field', 'field_test', 'filter', 'user', 'text', 'edit');

  /**
   * Sets the default field storage backend for fields created during tests.
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('entity_test', array('entity_test', 'entity_test_rev'));
    $this->installConfig(array('field', 'filter'));
  }

  /**
   * Creates a field and an instance of it.
   *
   * @param string $field_name
   *   The field name.
   * @param string $type
   *   The field type.
   * @param int $cardinality
   *   The field's cardinality.
   * @param string $label
   *   The field's label (used everywhere: widget label, formatter label).
   * @param array $instance_settings
   * @param string $widget_type
   *   The widget type.
   * @param array $widget_settings
   *   The widget settings.
   * @param string $formatter_type
   *   The formatter type.
   * @param array $formatter_settings
   *   The formatter settings.
   */
  public function createFieldWithInstance($field_name, $type, $cardinality, $label, $instance_settings, $widget_type, $widget_settings, $formatter_type, $formatter_settings) {
    $field = $field_name . '_field';
    $this->$field = entity_create('field_config', array(
      'name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => $type,
      'cardinality' => $cardinality,
    ));
    $this->$field->save();

    $instance = $field_name . '_instance';
    $this->$instance = entity_create('field_instance_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => $label,
      'description' => $label,
      'weight' => mt_rand(0, 127),
      'settings' => $instance_settings,
    ));
    $this->$instance->save();

    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => $widget_type,
        'label' => $label,
        'settings' => $widget_settings,
      ))
      ->save();

    entity_get_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'label' => 'above',
        'type' => $formatter_type,
        'settings' => $formatter_settings
      ))
      ->save();
  }

}
