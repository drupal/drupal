<?php

/**
 * @file
 * Contains \Drupal\options\Tests\OptionsDynamicValuesTestBase.
 */

namespace Drupal\options\Tests;

use Drupal\field\Tests\FieldTestBase;

/**
 * Base class for testing allowed values of options fields.
 */
abstract class OptionsDynamicValuesTestBase extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('options', 'entity_test', 'options_test');

  /**
   * The created entity.
   *
   * @var \Drupal\Core\Entity\Entity
   */
  protected $entity;

  function setUp() {
    parent::setUp();

    $this->field_name = 'test_options';
    entity_create('field_config', array(
      'name' => $this->field_name,
      'entity_type' => 'entity_test_rev',
      'type' => 'list_text',
      'cardinality' => 1,
      'settings' => array(
        'allowed_values_function' => 'options_test_dynamic_values_callback',
      ),
    ))->save();
    $this->instance = entity_create('field_instance_config', array(
      'field_name' => $this->field_name,
      'entity_type' => 'entity_test_rev',
      'bundle' => 'entity_test_rev',
      'required' => TRUE,
    ))->save();
    entity_get_form_display('entity_test_rev', 'entity_test_rev', 'default')
      ->setComponent($this->field_name, array(
        'type' => 'options_select',
      ))
      ->save();

    // Create an entity and prepare test data that will be used by
    // options_test_dynamic_values_callback().
    $values = array(
      'user_id' => mt_rand(1, 10),
      'name' => $this->randomName(),
    );
    $this->entity = entity_create('entity_test_rev', $values);
    $this->entity->save();
    $this->test = array(
      'label' => $this->entity->label(),
      'uuid' => $this->entity->uuid(),
      'bundle' => $this->entity->bundle(),
      'uri' => $this->entity->url(),
    );
  }
}
