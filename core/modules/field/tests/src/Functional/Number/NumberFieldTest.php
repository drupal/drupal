<?php

namespace Drupal\Tests\field\Functional\Number;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the creation of numeric fields.
 *
 * @group field
 */
class NumberFieldTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'entity_test', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
   * Test decimal field.
   */
  public function testNumberDecimalField() {
    // Create a field with settings to validate.
    $field_name = mb_strtolower($this->randomMachineName());
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
    $this->assertRaw('placeholder="0.00"');

    // Submit a signed decimal value within the allowed precision and scale.
    $value = '-1234.5678';
    $edit = [
      "{$field_name}[0][value]" => $value,
    ];
    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains('entity_test ' . $id . ' has been created.');
    $this->assertRaw($value);

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
      $this->assertRaw(t('%name must be a number.', ['%name' => $field_name]));
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
      $this->assertRaw(t('%name must be a number.', ['%name' => $field_name]));
    }
  }

  /**
   * Test integer field.
   */
  public function testNumberIntegerField() {
    $minimum = rand(-4000, -2000);
    $maximum = rand(2000, 4000);

    // Create a field with settings to validate.
    $field_name = mb_strtolower($this->randomMachineName());
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
    $this->assertRaw('placeholder="4"');

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
    $this->assertRaw(t('%name must be higher than or equal to %minimum.', ['%name' => $field_name, '%minimum' => $minimum]));

    // Try to set a decimal value
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][value]" => 1.5,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertRaw(t('%name is not a valid number.', ['%name' => $field_name]));

    // Try to set a value above the maximum value
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][value]" => $maximum + 1,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertRaw(t('%name must be lower than or equal to %maximum.', ['%name' => $field_name, '%maximum' => $maximum]));

    // Try to set a wrong integer value.
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][value]" => '20-40',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertRaw(t('%name must be a number.', ['%name' => $field_name]));

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
      $this->assertRaw($valid_entry);
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
  }

  /**
  * Test float field.
  */
  public function testNumberFloatField() {
    // Create a field with settings to validate.
    $field_name = mb_strtolower($this->randomMachineName());
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
    $this->assertRaw('placeholder="0.00"');

    // Submit a signed decimal value within the allowed precision and scale.
    $value = '-1234.5678';
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
    $this->assertRaw(round($value, 2));

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
      $this->assertRaw(t('%name must be a number.', ['%name' => $field_name]));
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
      $this->assertRaw(t('%name must be a number.', ['%name' => $field_name]));
    }
  }

  /**
   * Tests setting the minimum value of a float field through the interface.
   */
  public function testCreateNumberFloatField() {
    // Create a float field.
    $field_name = mb_strtolower($this->randomMachineName());
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
  }

  /**
   * Tests setting the minimum value of a decimal field through the interface.
   */
  public function testCreateNumberDecimalField() {
    // Create a decimal field.
    $field_name = mb_strtolower($this->randomMachineName());
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
   */
  public function assertSetMinimumValue($field, $minimum_value) {
    $field_configuration_url = 'entity_test/structure/entity_test/fields/entity_test.entity_test.' . $field->getName();

    // Set the minimum value.
    $edit = [
      'settings[min]' => $minimum_value,
    ];
    $this->drupalGet($field_configuration_url);
    $this->submitForm($edit, 'Save settings');
    // Check if an error message is shown.
    $this->assertNoRaw(t('%name is not a valid number.', ['%name' => t('Minimum')]));
    // Check if a success message is shown.
    $this->assertRaw(t('Saved %label configuration.', ['%label' => $field->getLabel()]));
    // Check if the minimum value was actually set.
    $this->drupalGet($field_configuration_url);
    $this->assertSession()->fieldValueEquals('edit-settings-min', $minimum_value);
  }

}
