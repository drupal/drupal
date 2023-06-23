<?php

namespace Drupal\Tests\options\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the Options field UI functionality.
 *
 * @group options
 */
class OptionsFieldUITest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'options',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Machine name of the created content type.
   *
   * @var string
   */
  protected $type;

  /**
   * Name of the option field.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Admin path to manage field storage settings.
   *
   * @var string
   */
  protected $adminPath;

  /**
   * Node form path for created content type.
   *
   * @var string
   */
  protected $nodeFormPath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create test user.
    $admin_user = $this->drupalCreateUser([
      'bypass node access',
      'administer node fields',
      'administer node display',
    ]);
    $this->drupalLogin($admin_user);

    $type = $this->drupalCreateContentType(['type' => 'plan']);
    $this->type = $type->id();
    $this->nodeFormPath = 'node/add/' . $this->type;
  }

  /**
   * Tests option types allowed values.
   *
   * @dataProvider providerTestOptionsAllowedValues
   */
  public function testOptionsAllowedValues($option_type, $options, $is_string_option) {
    $this->fieldName = 'field_options_text';
    $this->createOptionsField($option_type);
    $page = $this->getSession()->getPage();

    $this->drupalGet($this->adminPath);

    $i = 0;
    foreach ($options as $option_key => $option_label) {
      $page->fillField("settings[allowed_values][table][$i][item][label]", $option_label);
      // Add keys if not string option list.
      if (!$is_string_option) {
        $page->fillField("settings[allowed_values][table][$i][item][key]", $option_key);
      }
      $page->pressButton('Add another item');
      $i++;
      $this->assertSession()->waitForElementVisible('css', "[name='settings[allowed_values][table][$i][item][label]']");
    }
    $page->pressButton('Save field settings');

    // Test the order of the option list on node form.
    $this->drupalGet($this->nodeFormPath);
    $this->assertNodeFormOrder(['- None -', 'First', 'Second', 'Third']);

    // Test the order of the option list on admin path.
    $this->drupalGet($this->adminPath);
    $this->assertOrder(['First', 'Second', 'Third', ''], $is_string_option);
    $drag_handle = $page->find('css', '[data-drupal-selector="edit-settings-allowed-values-table-0"] .tabledrag-handle');
    $target = $page->find('css', '[data-drupal-selector="edit-settings-allowed-values-table-2"]');

    // Change the order the items appear.
    $drag_handle->dragTo($target);
    $this->assertOrder(['Second', 'Third', 'First', ''], $is_string_option);
    $page->pressButton('Save field settings');

    $this->drupalGet($this->nodeFormPath);
    $this->assertNodeFormOrder(['- None -', 'Second', 'Third', 'First']);

    $this->drupalGet($this->adminPath);

    // Confirm the change in order was saved.
    $this->assertOrder(['Second', 'Third', 'First', ''], $is_string_option);

    // Delete an item.
    $page->pressButton('remove_row_button__1');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertOrder(['Second', 'First', ''], $is_string_option);
    $page->pressButton('Save field settings');

    $this->drupalGet($this->nodeFormPath);
    $this->assertNodeFormOrder(['- None -', 'Second', 'First']);

    $this->drupalGet($this->adminPath);

    // Confirm the item removal was saved.
    $this->assertOrder(['Second', 'First', ''], $is_string_option);
  }

  /**
   * Asserts the order of provided option list on admin path.
   *
   * @param array $expected
   *   Expected order.
   * @param bool $is_string_option
   *   Whether the request is for string option list.
   */
  protected function assertOrder($expected, $is_string_option) {
    $page = $this->getSession()->getPage();
    if ($is_string_option) {
      $inputs = $page->findAll('css', '.draggable .form-text.machine-name-source');
    }
    else {
      $inputs = $page->findAll('css', '.draggable .form-text');
    }
    foreach ($expected as $step => $expected_input_value) {
      $value = $inputs[$step]->getValue();
      $this->assertSame($expected_input_value, $value, "Item $step should be $expected_input_value, but got $value");
    }
  }

  /**
   * Asserts the order of provided option list on node form.
   *
   * @param array $expected
   *   Expected order.
   */
  protected function assertNodeFormOrder($expected) {
    $elements = $this->assertSession()->selectExists('field_options_text')->findAll('css', 'option');
    $elements = array_map(function ($element) {
      return $element->getText();
    }, $elements);
    $this->assertSame($expected, $elements);
  }

  /**
   * Helper function to create list field of a given type.
   *
   * @param string $type
   *   One of 'list_integer', 'list_float' or 'list_string'.
   */
  protected function createOptionsField($type) {
    // Create a field.
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => $type,
    ])->save();
    FieldConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'bundle' => $this->type,
    ])->save();

    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', $this->type)
      ->setComponent($this->fieldName)
      ->save();

    $this->adminPath = 'admin/structure/types/manage/' . $this->type . '/fields/node.' . $this->type . '.' . $this->fieldName . '/storage';
  }

  /**
   * Data provider for testOptionsAllowedValues().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - Option type.
   *   - Array of option type values.
   *   - Whether option type is string type or not.
   */
  public function providerTestOptionsAllowedValues() {
    return [
      'List integer' => [
        'list_integer',
        [1 => 'First', 2 => 'Second', 3 => 'Third'],
        FALSE,
      ],
      'List float' => [
        'list_float',
        ['0.1' => 'First', '0.2' => 'Second', '0.3' => 'Third'],
        FALSE,
      ],
      'List string' => [
        'list_string',
        ['first' => 'First', 'second' => 'Second', 'third' => 'Third'],
        TRUE,
      ],
    ];
  }

}
