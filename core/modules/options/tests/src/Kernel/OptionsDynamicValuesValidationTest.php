<?php

declare(strict_types=1);

namespace Drupal\Tests\options\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the Options field allowed values function.
 *
 * @group options
 */
class OptionsDynamicValuesValidationTest extends OptionsFieldUnitTestBase {

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
   * Test data.
   */
  protected array $test;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    FieldStorageConfig::create([
      'field_name' => 'test_options',
      'entity_type' => 'entity_test_rev',
      'type' => 'list_string',
      'cardinality' => 1,
      'settings' => [
        'allowed_values_function' => '\Drupal\options_test\OptionsAllowedValues::dynamicValues',
      ],
    ])->save();

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

    $this->test = [
      'label' => $this->entity->label(),
      'uuid' => $this->entity->uuid(),
      'bundle' => $this->entity->bundle(),
      'uri' => $this->entity->toUrl()->toString(),
    ];
  }

  /**
   * Tests that allowed values function gets the entity.
   */
  public function testDynamicAllowedValues(): void {
    // Verify that validation passes against every value we had.
    foreach ($this->test as $key => $value) {
      $this->entity->test_options->value = $value;
      $violations = $this->entity->test_options->validate();
      $this->assertCount(0, $violations, "$key is a valid value");
    }

    // Now verify that validation does not pass against anything else.
    foreach ($this->test as $key => $value) {
      $this->entity->test_options->value = is_numeric($value) ? (100 - $value) : ('X' . $value);
      $violations = $this->entity->test_options->validate();
      $this->assertCount(1, $violations, "$key is not a valid value");
    }
  }

}
