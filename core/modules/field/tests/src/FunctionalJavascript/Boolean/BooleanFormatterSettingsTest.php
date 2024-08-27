<?php

declare(strict_types=1);

namespace Drupal\Tests\field\FunctionalJavascript\Boolean;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the Boolean field formatter settings.
 *
 * @group field
 */
class BooleanFormatterSettingsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'field_ui', 'text', 'node', 'user'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The name of the entity bundle that is created in the test.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The name of the Boolean field to use for testing.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a content type. Use Node because it has Field UI pages that work.
    $type_name = $this->randomMachineName(8) . '_test';
    $type = $this->drupalCreateContentType(['name' => $type_name, 'type' => $type_name]);
    $this->bundle = $type->id();

    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
      'administer node display',
      'bypass node access',
      'administer nodes',
    ]);
    $this->drupalLogin($admin_user);

    $this->fieldName = $this->randomMachineName(8);

    $field_storage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'boolean',
    ]);
    $field_storage->save();

    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->bundle,
      'label' => $this->randomMachineName(),
    ]);
    $instance->save();

    $display = \Drupal::service('entity_display.repository')->getViewDisplay('node', $this->bundle)
      ->setComponent($this->fieldName, [
        'type' => 'boolean',
        'settings' => [],
      ]);
    $display->save();
  }

  /**
   * Tests the formatter settings page for the Boolean formatter.
   */
  public function testBooleanFormatterSettings(): void {
    // List the options we expect to see on the settings form. Omit the one
    // with the Unicode check/x characters, which does not appear to work
    // well in BrowserTestBase.
    $options = [
      'Yes / No',
      'True / False',
      'On / Off',
      'Enabled / Disabled',
      '1 / 0',
      'Custom',
    ];

    // For several different values of the field settings, test that the
    // options, including default, are shown correctly.
    $settings = [
      ['Yes', 'No'],
      ['On', 'Off'],
      ['TRUE', 'FALSE'],
    ];

    $assert_session = $this->assertSession();
    foreach ($settings as $values) {
      // Set up the field settings.
      $this->drupalGet('admin/structure/types/manage/' . $this->bundle . '/fields/node.' . $this->bundle . '.' . $this->fieldName);
      $this->submitForm([
        'settings[on_label]' => $values[0],
        'settings[off_label]' => $values[1],
      ], 'Save settings');

      // Open the Manage Display page and trigger the field settings form.
      $this->drupalGet('admin/structure/types/manage/' . $this->bundle . '/display');
      $this->getSession()->getPage()->pressButton($this->fieldName . '_settings_edit');
      $assert_session->waitForElement('css', '.ajax-new-content');

      // Test that the settings options are present in the correct format.
      foreach ($options as $string) {
        $assert_session->pageTextContains($string);
      }
      $assert_session->pageTextContains("Field settings ({$values[0]} / {$values[1]})");
    }
  }

}
