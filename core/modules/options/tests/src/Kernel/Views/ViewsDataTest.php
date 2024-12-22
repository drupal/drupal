<?php

declare(strict_types=1);

namespace Drupal\Tests\options\Kernel\Views;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Test to ensure views data is properly created for the Options module.
 *
 * @group views
 */
class ViewsDataTest extends OptionsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'options',
    'options_test',
    'entity_test',
    'views',
  ];

  /**
   * The field storage.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface
   */
  protected $fieldStorage;

  /**
   * @var int
   */
  protected int $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $field_name = 'test_options';
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'list_string',
      'cardinality' => 1,
      'settings' => [
        'allowed_values_function' => '\Drupal\options_test\OptionsAllowedValues::dynamicValues',
      ],
    ]);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'required' => TRUE,
    ])->save();
  }

  /**
   * Tests the option module's implementation of hook_field_views_data().
   */
  public function testOptionsFieldViewsData(): void {
    $field_data = \Drupal::service('views.views_data')->get('entity_test__test_options');

    // Check that the options module has properly overridden default views data.
    $test_options_field = $field_data['test_options_value'];
    $this->assertEquals('string_list_field', $test_options_field['argument']['id'], 'Argument handler is properly set for fields with allowed value callbacks.');
    $this->assertEquals('list_field', $test_options_field['filter']['id'], 'Filter handler is properly set for fields with allowed value callbacks.');
  }

}
