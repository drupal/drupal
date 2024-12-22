<?php

declare(strict_types=1);

namespace Drupal\Tests\options\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the options allowed values api.
 *
 * @group options
 */
class OptionsDynamicValuesApiTest extends OptionsFieldUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'options_test',
    'node',
  ];

  /**
   * The created entity.
   */
  protected EntityInterface $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => 'test_options',
      'entity_type' => 'entity_test_rev',
      'type' => 'list_string',
      'cardinality' => 1,
      'settings' => [
        'allowed_values_function' => '\Drupal\options_test\OptionsAllowedValues::dynamicValues',
      ],
    ]);
    $this->fieldStorage->save();

    FieldConfig::create([
      'field_name' => 'test_options',
      'entity_type' => 'entity_test_rev',
      'bundle' => 'entity_test_rev',
      'required' => TRUE,
    ])->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('entity_test_rev', 'entity_test_rev')
      ->setComponent('test_options', [
        'type' => 'options_select',
      ])
      ->save();

    // Create an entity and prepare test data that will be used by
    // \Drupal\options_test\OptionsAllowedValues::dynamicValues().
    $values = [
      'user_id' => 2,
      'name' => $this->randomMachineName(),
    ];
    $this->entity = EntityTestRev::create($values);
    $this->entity->save();
  }

  /**
   * Tests options_allowed_values().
   *
   * @see \Drupal\options_test\OptionsAllowedValues::dynamicValues()
   */
  public function testOptionsAllowedValues(): void {
    // Test allowed values without passed $items.
    $values = options_allowed_values($this->fieldStorage);
    $this->assertEquals([], $values);

    $values = options_allowed_values($this->fieldStorage, $this->entity);

    $expected_values = [
      $this->entity->label(),
      $this->entity->toUrl()->toString(),
      $this->entity->uuid(),
      $this->entity->bundle(),
    ];
    $expected_values = array_combine($expected_values, $expected_values);
    $this->assertEquals($expected_values, $values);
  }

}
