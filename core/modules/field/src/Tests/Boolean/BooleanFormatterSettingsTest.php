<?php

namespace Drupal\field\Tests\Boolean;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the Boolean field formatter settings.
 *
 * @group field
 */
class BooleanFormatterSettingsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field', 'field_ui', 'text', 'node', 'user'];

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
  protected function setUp() {
    parent::setUp();

    // Create a content type. Use Node because it has Field UI pages that work.
    $type_name = Unicode::strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType(['name' => $type_name, 'type' => $type_name]);
    $this->bundle = $type->id();

    $admin_user = $this->drupalCreateUser(['access content', 'administer content types', 'administer node fields', 'administer node display', 'bypass node access', 'administer nodes']);
    $this->drupalLogin($admin_user);

    $this->fieldName = Unicode::strtolower($this->randomMachineName(8));

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

    $display = entity_get_display('node', $this->bundle, 'default')
      ->setComponent($this->fieldName, [
        'type' => 'boolean',
        'settings' => [],
      ]);
    $display->save();
  }

  /**
   * Tests the formatter settings page for the Boolean formatter.
   */
  public function testBooleanFormatterSettings() {
    // List the options we expect to see on the settings form. Omit the one
    // with the Unicode check/x characters, which does not appear to work
    // well in WebTestBase.
    $options = [
      'Yes / No',
      'True / False',
      'On / Off',
      'Enabled / Disabled',
      '1 / 0',
      'Custom',
    ];

    // Define what the "default" option should look like, depending on the
    // field settings.
    $default = 'Field settings (@on / @off)';

    // For several different values of the field settings, test that the
    // options, including default, are shown correctly.
    $settings = [
      ['Yes', 'No'],
      ['On', 'Off'],
      ['TRUE', 'FALSE'],
    ];

    foreach ($settings as $values) {
      // Set up the field settings.
      $this->drupalGet('admin/structure/types/manage/' . $this->bundle . '/fields/node.' . $this->bundle . '.' . $this->fieldName);
      $this->drupalPostForm(NULL, [
        'settings[on_label]' => $values[0],
        'settings[off_label]' => $values[1],
      ], 'Save settings');

      // Open the Manage Display page and trigger the field settings form.
      $this->drupalGet('admin/structure/types/manage/' . $this->bundle . '/display');
      $this->drupalPostAjaxForm(NULL, [], $this->fieldName . '_settings_edit');

      // Test that the settings options are present in the correct format.
      foreach ($options as $string) {
        $this->assertText($string);
      }
      $this->assertText(SafeMarkup::format($default, ['@on' => $values[0], '@off' => $values[1]]));
    }

    foreach ($settings as $values) {
      $this->drupalGet('admin/structure/types/manage/' . $this->bundle . '/display');
      $result = $this->xpath('//div[contains(@class, :class) and contains(text(), :text)]', [':class' => 'field-plugin-summary', ':text' => 'Display: TRUE / FALSE']);
      $this->assertEqual(count($result), 1, "Boolean formatter settings summary exist.");
    }
  }

}
