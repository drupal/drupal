<?php

declare(strict_types=1);

namespace Drupal\Tests\options\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiJSTestTrait;

/**
 * Tests the Options field UI functionality.
 *
 * @group options
 * @group #slow
 */
class OptionsFieldUITest extends WebDriverTestBase {

  use FieldUiJSTestTrait;

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
   * Tests that the allowed options are available to the default value widget.
   */
  public function testDefaultValueOptions(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $bundle_path = 'admin/structure/types/manage/' . $this->type;
    // Create a field of type list:string.
    $this->fieldUIAddNewFieldJS($bundle_path, 'test_string_list', 'Test string list', 'list_string', FALSE);
    $page->findField('field_storage[subform][settings][allowed_values][table][0][item][label]')->setValue('first');
    $assert_session->assertWaitOnAjaxRequest();
    $page->findField('set_default_value')->setValue(TRUE);
    // Assert that the option added in the subform is available to the default
    // value field.
    $this->assertSession()->optionExists('default_value_input[field_test_string_list]', 'first');
    $page->pressButton('Add another item');
    $this->assertNotNull($assert_session->waitForElement('css', "[name='field_storage[subform][settings][allowed_values][table][1][item][label]']"));
    $page->findField('field_storage[subform][settings][allowed_values][table][1][item][label]')->setValue('second');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->optionExists('default_value_input[field_test_string_list]', 'second');
    $page->selectFieldOption('default_value_input[field_test_string_list]', 'second');
    $page->pressButton('Save settings');
    $assert_session->pageTextContains('Saved Test string list configuration.');

    // Create a field of type list:integer.
    $this->fieldUIAddNewFieldJS($bundle_path, 'test_int_list', 'Test int list', 'list_integer', FALSE);
    $page->findField('field_storage[subform][settings][allowed_values][table][0][item][label]')->setValue('first');
    $assert_session->assertWaitOnAjaxRequest();
    // Assert that no validation is performed.
    $assert_session->statusMessageNotContains('Value field is required.');
    $page->findField('field_storage[subform][settings][allowed_values][table][0][item][key]')->setValue(1);
    $assert_session->assertWaitOnAjaxRequest();
    $page->findField('set_default_value')->setValue(TRUE);
    // Assert that the option added in the subform is available to the default
    // value field.
    $this->assertSession()->optionExists('default_value_input[field_test_int_list]', 'first');
    $page->selectFieldOption('default_value_input[field_test_int_list]', 'first');
    $page->pressButton('Save settings');
    $assert_session->pageTextContains('Saved Test int list configuration.');
  }

  /**
   * Helper function to create list field of a given type.
   *
   * @param string $type
   *   One of 'list_integer', 'list_float' or 'list_string'.
   */
  protected function createOptionsField($type): void {
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

    $this->adminPath = 'admin/structure/types/manage/' . $this->type . '/fields/node.' . $this->type . '.' . $this->fieldName;
  }

  /**
   * Tests `list_string` machine name with special characters.
   */
  public function testMachineNameSpecialCharacters(): void {
    $this->fieldName = 'field_options_text';
    $this->createOptionsField('list_string');
    $this->drupalGet($this->adminPath);

    $label_element_name = "field_storage[subform][settings][allowed_values][table][0][item][label]";
    $this->getSession()->getPage()->fillField($label_element_name, 'Hello world');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->exposeOptionMachineName(1);

    $key_element_name = "field_storage[subform][settings][allowed_values][table][0][item][key]";

    // Ensure that the machine name was generated correctly.
    $this->assertSession()->fieldValueEquals($key_element_name, 'hello_world');

    // Ensure that the machine name can be overridden with a value that includes
    // special characters.
    $this->getSession()->getPage()->fillField($key_element_name, '.hello #world');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->pressButton('Save settings');
    $this->assertSession()->statusMessageContains("Saved {$this->fieldName} configuration.");

    // Ensure that the machine name was saved correctly.
    $allowed_values = FieldStorageConfig::loadByName('node', $this->fieldName)
      ->getSetting('allowed_values');
    $this->assertSame(['.hello #world'], array_keys($allowed_values));
  }

  /**
   * Exposes the machine name input for a row.
   *
   * @param int $row
   *   The row number.
   */
  private function exposeOptionMachineName(int $row): void {
    $index = $row - 1;
    $rows = $this->getSession()->getPage()->findAll('css', '#allowed-values-order tr.draggable');
    $this->assertSession()->buttonExists('Edit', $rows[$index])->click();
    $this->assertSession()->waitForElementVisible('css', "[name='field_storage[subform][settings][allowed_values][table][$index][item][key]']");
  }

}
