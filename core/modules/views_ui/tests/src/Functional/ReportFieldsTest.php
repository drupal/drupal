<?php

declare(strict_types=1);

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
  protected static $modules = ['entity_test'];

  /**
   * Tests the Views fields report page.
   */
  public function testReportFields(): void {
    $this->drupalGet('admin/reports/fields/views-fields');
    $this->assertSession()->pageTextContains('Used in views');
    $this->assertSession()->pageTextContains('No fields have been used in views yet.');

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

    // Assert that the newly created field appears in the overview.
    $this->drupalGet('admin/reports/fields/views-fields');
    $this->assertSession()->responseContains('<td>field_test</td>');
    $this->assertSession()->responseContains('>test_field_field_test</a>');
    $this->assertSession()->pageTextContains('Used in views');
  }

}
