<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the Views fields report page.
 *
 * @group views_ui
 */
class ReportFieldsTest extends UITestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_field_field_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test'];

  /**
   * Tests the Views fields report page.
   */
  public function testReportFields() {
    $this->drupalGet('admin/reports/fields/views-fields');
    $this->assertRaw('Used in views', 'Title appears correctly');
    $this->assertRaw('No fields have been used in views yet.', 'No results message appears correctly.');

    // Set up the field_test field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_test',
      'type' => 'integer',
      'entity_type' => 'entity_test',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field->save();

    $this->drupalGet('admin/reports/fields/views-fields');
    // Assert that the newly created field appears in the overview.
    $this->assertRaw('<td>field_test</td>', 'Field name appears correctly');
    $this->assertRaw('>test_field_field_test</a>', 'View name appears correctly');
    $this->assertRaw('Used in views', 'Title appears correctly');
  }

}
