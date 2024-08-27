<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Functional\Number;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the creation of numeric fields.
 *
 * @group field
 */
class NumberFieldTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'entity_test', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
      'administer content types',
      'administer node fields',
      'administer node display',
      'bypass node access',
      'administer entity_test fields',
    ]));
  }

  /**
   * Tests decimal field.
   */
  public function testNumberDecimalField(): void {
    // Create a field with settings to validate.
    $field_name = $this->randomMachineName();
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'decimal',
      'settings' => ['precision' => 8, 'scale' => 4],
    ])->save();
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, [
        'type' => 'number',
        'settings' => [
          'placeholder' => '0.00',
        ],
      ])
      ->save();
    $display_repository->getViewDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, [
        'type' => 'number_decimal',
      ])
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][value]", '');
    $this->assertSession()->responseContains('placeholder="0.00"');

    // Submit a signed decimal value within the allowed precision and scale.
    $value = '-1234.5678';
    $edit = [
      "{$field_name}[0][value]" => $value,
    ];
    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains('entity_test ' . $id . ' has been created.');
    $this->assertSession()->responseContains($value);

    // Try to create entries with more than one decimal separator; assert fail.
    $wrong_entries = [
      '3.14.159',
      '0..45469',
      '..4589',
      '6.459.52',
      '6.3..25',
    ];

    foreach ($wrong_entries as $wrong_entry) {
      $this->drupalGet('entity_test/add');
      $edit = [
        "{$field_name}[0][value]" => $wrong_entry,
      ];
      $this->submitForm($edit, 'Save');
      $this->assertSession()->pageTextContains("{$field_name} must be a number.");
    }

    // Try to create entries with minus sign not in the first position.
    $wrong_entries = [
      '3-3',
      '4-',
      '1.3-',
      '1.2-4',
      '-10-10',
    ];

    foreach ($wrong_entries as $wrong_entry) {
      $this->drupalGet('entity_test/add');
      $edit = [
        "{$field_name}[0][value]" => $wrong_entry,
      ];
      $this->submitForm($edit, 'Save');
      $this->assertSession()->pageTextContains("{$field_name} must be a number.");
    }
  }

  /**
   * Tests integer field.
   */
  public function testNumberIntegerField(): void {
    $minimum = rand(-4000, -2000);
    $maximum = rand(2000, 4000);

    // Create a field with settings to validate.
    $field_name = $this->randomMachineName();
    $storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'integer',
    ]);
    $storage->save();

    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'settings' => [
        'min' => $minimum,
        'max' => $maximum,
        'prefix' => 'ThePrefix',
      ],
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, [
        'type' => 'number',
        'settings' => [
          'placeholder' => '4',
        ],
      ])
      ->save();
    $display_repository->getViewDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, [
        'type' => 'number_integer',
        'settings' => [
          'prefix_suffix' => FALSE,
        ],
      ])
      ->save();

    // Check the storage schema.
    $expected = [
      'columns' => [
        'value' => [
          'type' => 'int',
          'unsigned' => '',
          'size' => 'normal',
        ],
      ],
      'unique keys' => [],
      'indexes' => [],
      'foreign keys' => [],
    ];
    $this->assertEquals($expected, $storage->getSchema());

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][value]", '');
    $this->assertSession()->responseContains('placeholder="4"');

    // Submit a valid integer
    $value = rand($minimum, $maximum);
    $edit = [
      "{$field_name}[0][value]" => $value,
    ];
    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains('entity_test ' . $id . ' has been created.');

    // Try to set a value below the minimum value
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][value]" => $minimum - 1,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("{$field_name} must be higher than or equal to {$minimum}.");

    // Try to set a decimal value
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][value]" => 1.5,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("{$field_name} is not a valid number.");

    // Try to set a value above the maximum value
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][value]" => $maximum + 1,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("{$field_name} must be lower than or equal to {$maximum}.");

    // Try to set a wrong integer value.
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][value]" => '20-40',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("{$field_name} must be a number.");

    // Test with valid entries.
    $valid_entries = [
      '-1234',
      '0',
      '1234',
    ];

    foreach ($valid_entries as $valid_entry) {
      $this->drupalGet('entity_test/add');
      $edit = [
        "{$field_name}[0][value]" => $valid_entry,
      ];
      $this->submitForm($edit, 'Save');
      preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
      $id = $match[1];
      $this->assertSession()->pageTextContains('entity_test ' . $id . ' has been created.');
      $this->assertSession()->responseContains($valid_entry);
      // Verify that the "content" attribute is not present since the Prefix is
      // not being displayed.
      $this->assertSession()->elementNotExists('xpath', '//div[@content="' . $valid_entry . '"]');
    }

    // Test for the content attribute when a Prefix is displayed. Presumably this also tests for the attribute when a Suffix is displayed.
    $display_repository->getViewDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, [
        'type' => 'number_integer',
        'settings' => [
          'prefix_suffix' => TRUE,
        ],
      ])
      ->save();
    $integer_value = '123';
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][value]" => $integer_value,
    ];
    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains('entity_test ' . $id . ' has been created.');
    $this->drupalGet('entity_test/' . $id);
    // Verify that the "content" attribute has been set to the value of the
    // field, and the prefix is being displayed.
    $this->assertSession()->elementTextContains('xpath', '//div[@content="' . $integer_value . '"]', 'ThePrefix' . $integer_value);

    $field_configuration_url = 'entity_test/structure/entity_test/fields/entity_test.entity_test.' . $field_name;
    $this->drupalGet($field_configuration_url);

    // Tests Number validation messages.
    $edit = [
      'settings[min]' => 10,
      'settings[max]' => 8,
    ];
    $this->submitForm($edit, 'Save settings');
    $this->assertSession()->pageTextContains("The minimum value must be less than or equal to {$edit['settings[max]']}.");
  }

  /**
   * Tests float field.
   */
  public function testNumberFloatField(): void {
    // Create a field with settings to validate.
    $field_name = $this->randomMachineName();
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'float',
    ])->save();

    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, [
        'type' => 'number',
        'settings' => [
          'placeholder' => '0.00',
        ],
      ])
      ->save();

    $display_repository->getViewDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, [
        'type' => 'number_decimal',
      ])
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][value]", '');
    $this->assertSession()->responseContains('placeholder="0.00"');

    // Submit a signed decimal value within the allowed precision and scale.
    $value = -1234.5678;
    $edit = [
      "{$field_name}[0][value]" => $value,
    ];
    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains('entity_test ' . $id . ' has been created.');

    // Ensure that the 'number_decimal' formatter displays the number with the
    // expected rounding.
    $this->drupalGet('entity_test/' . $id);
    $this->assertSession()->responseContains(round($value, 2));

    // Try to create entries with more than one decimal separator; assert fail.
    $wrong_entries = [
      '3.14.159',
      '0..45469',
      '..4589',
      '6.459.52',
      '6.3..25',
    ];

    foreach ($wrong_entries as $wrong_entry) {
      $this->drupalGet('entity_test/add');
      $edit = [
        "{$field_name}[0][value]" => $wrong_entry,
      ];
      $this->submitForm($edit, 'Save');
      $this->assertSession()->pageTextContains("{$field_name} must be a number.");
    }

    // Try to create entries with minus sign not in the first position.
    $wrong_entries = [
      '3-3',
      '4-',
      '1.3-',
      '1.2-4',
      '-10-10',
    ];

    foreach ($wrong_entries as $wrong_entry) {
      $this->drupalGet('entity_test/add');
      $edit = [
        "{$field_name}[0][value]" => $wrong_entry,
      ];
      $this->submitForm($edit, 'Save');
      $this->assertSession()->pageTextContains("{$field_name} must be a number.");
    }
  }

  /**
   * Tests setting minimum values through the interface.
   */
  public function testMinimumValues(): void {
    // Create a float field.
    $field_name = $this->randomMachineName();
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'float',
    ])->save();

    $field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field->save();

    // Set the minimum value to a float value.
    $this->assertSetMinimumValue($field, 0.0001);
    // Set the minimum value to an integer value.
    $this->assertSetMinimumValue($field, 1);

    // Create a decimal field.
    $field_name = $this->randomMachineName();
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'decimal',
    ])->save();

    $field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field->save();

    // Set the minimum value to a decimal value.
    $this->assertSetMinimumValue($field, 0.1);
    // Set the minimum value to an integer value.
    $this->assertSetMinimumValue($field, 1);
  }

  /**
   * Helper function to set the minimum value of a field.
   *
   * @internal
   */
  public function assertSetMinimumValue(FieldConfigInterface $field, $minimum_value): void {
    $field_configuration_url = 'entity_test/structure/entity_test/fields/entity_test.entity_test.' . $field->getName();

    // Set the minimum value.
    $edit = [
      'settings[min]' => $minimum_value,
    ];
    $this->drupalGet($field_configuration_url);
    $this->submitForm($edit, 'Save settings');
    // Check if an error message is shown.
    $this->assertSession()->pageTextNotContains("Minimum is not a valid number.");
    // Check if a success message is shown.
    $this->assertSession()->pageTextContains("Saved {$field->getLabel()} configuration.");
    // Check if the minimum value was actually set.
    $this->drupalGet($field_configuration_url);
    $this->assertSession()->fieldValueEquals('edit-settings-min', $minimum_value);
  }

}
