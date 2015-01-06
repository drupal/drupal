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
  public static $modules = ['options', 'entity_test', 'options_test'];

  /**
   * The created entity.
   *
   * @var \Drupal\Core\Entity\Entity
   */
  protected $entity;

  /**
   * The field storage.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface
   */
  protected $fieldStorage;

  protected function setUp() {
    parent::setUp();

    $field_name = 'test_options';
    $this->fieldStorage = entity_create('field_storage_config', [
      'field_name' => $field_name,
      'entity_type' => 'entity_test_rev',
      'type' => 'list_string',
      'cardinality' => 1,
      'settings' => [
        'allowed_values_function' => 'options_test_dynamic_values_callback',
      ],
    ]);
    $this->fieldStorage->save();

    $this->field = entity_create('field_config', [
      'field_name' => $field_name,
      'entity_type' => 'entity_test_rev',
      'bundle' => 'entity_test_rev',
      'required' => TRUE,
    ])->save();
    entity_get_form_display('entity_test_rev', 'entity_test_rev', 'default')
      ->setComponent($field_name, [
        'type' => 'options_select',
      ])
      ->save();

    // Create an entity and prepare test data that will be used by
    // options_test_dynamic_values_callback().
    $values = [
      'user_id' => mt_rand(1, 10),
      'name' => $this->randomMachineName(),
    ];
    $this->entity = entity_create('entity_test_rev', $values);
    $this->entity->save();
    $this->test = [
      'label' => $this->entity->label(),
      'uuid' => $this->entity->uuid(),
      'bundle' => $this->entity->bundle(),
      'uri' => $this->entity->url(),
    ];
  }
}
