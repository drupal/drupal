<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiJSTestTrait;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the Field UI "Manage display" and "Manage form display" screens.
 *
 * @group field_ui
 */
class ManageDisplayTest extends WebDriverTestBase {

  use FieldUiTestTrait;
  use FieldUiJSTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field_ui',
    'field_test',
    'field_third_party_test',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * @var string
   */
  protected $type;

  /**
   * @var \Drupal\Core\Entity\entityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $displayStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');

    // Create a test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'administer users',
      'administer account settings',
      'administer user display',
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $type_name = $this->randomMachineName(8) . '_test';
    $type = $this->drupalCreateContentType(['name' => $type_name, 'type' => $type_name]);
    $this->type = $type->id();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Tests formatter settings.
   */
  public function testFormatterUI() {
    $manage_fields = 'admin/structure/types/manage/' . $this->type;
    $manage_display = $manage_fields . '/display';

    // Create a field, and a node with some data for the field.
    $this->fieldUIAddNewFieldJS($manage_fields, 'test', 'Test field');

    $display_id = 'node.' . $this->type . '.default';
    $displayStorage = $this->entityTypeManager->getStorage('entity_view_display');

    // Get the display options (formatter and settings) that were automatically
    // assigned for the 'default' display.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = $displayStorage->loadUnchanged($display_id);
    $display_options = $display->getComponent('field_test');
    $format = $display_options['type'];
    $default_settings = \Drupal::service('plugin.manager.field.formatter')->getDefaultSettings($format);
    $setting_name = key($default_settings);
    $setting_value = $display_options['settings'][$setting_name];

    // Display the "Manage display" screen and check that the expected formatter
    // is selected.
    $this->drupalGet($manage_display);

    $session = $this->getSession();
    $assert_session = $this->assertSession();
    $page = $session->getPage();

    // Find commonly used elements in this test.
    $button_save = $page->findButton('Save');
    $field_test_format_type = $page->findField('fields[field_test][type]');
    $field_test_drag_handle = $page->find('css', '#field-test .tabledrag-handle');
    $field_test_settings = $page->find('css', 'input[name="field_test_settings_edit"]');
    $weight_toggle = $page->find('css', '.tabledrag-toggle-weight');

    // Assert the format type field is visible and contains the expected
    // formatter.
    $this->assertTrue($field_test_format_type->isVisible());
    $this->assertEquals($format, $field_test_format_type->getValue());
    $assert_session->responseContains("$setting_name: $setting_value");

    // Validate the selectbox.
    $this->assertFieldSelectOptions($field_test_format_type, [
      'field_no_settings',
      'field_empty_test',
      'field_empty_setting',
      'field_test_default',
      'field_test_multiple',
      'field_test_with_prepare_view',
      'field_test_applicable',
    ]);

    // Ensure that fields can be hidden directly by dragging the element.
    $target = $page->find('css', '.region-hidden-message');
    $field_test_drag_handle->dragTo($target);
    $assert_session->assertExpectedAjaxRequest(1);

    $button_save->click();

    // Validate the changed display settings on the server.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = $displayStorage->loadUnchanged($display_id);
    $this->assertNull($display->getComponent('field_test'));

    // Switch to manual mode.
    $weight_toggle->click();
    $field_region = $page->findField('fields[field_test][region]');

    // Change the region to content using the region field.
    $this->assertEquals('hidden', $field_region->getValue());
    $field_region->setValue('content');

    // Confirm the region element retains focus after the AJAX update completes.
    $this->assertJsCondition('document.activeElement === document.querySelector("[name=\'fields[field_test][region]\']")');
    $button_save->click();

    // Change the format for the test field.
    $field_test_format_type->setValue('field_test_multiple');
    $assert_session->assertExpectedAjaxRequest(1);

    // Confirm the format element retains focus after the AJAX update completes.
    $this->assertJsCondition('document.activeElement === document.querySelector("[name=\'fields[field_test][type]\']")');

    $plugin_summary = $page->find('css', '#field-test .field-plugin-summary');
    $this->assertStringContainsString("test_formatter_setting_multiple: dummy test string", $plugin_summary->getText(), 'The expected summary is displayed.');

    // Submit the form and assert that
    // hook_field_formatter_settings_summary_alter() is called.
    $button_save->click();
    $assert_session->responseContains('field_test_field_formatter_settings_summary_alter');

    // Open the settings form for the test field.
    $field_test_settings->click();
    $assert_session->assertExpectedAjaxRequest(1);

    // Assert that the field added in
    // field_test_field_formatter_third_party_settings_form() is present.
    $field_third_party = $page->findField('fields[field_test][settings_edit_form][third_party_settings][field_third_party_test][field_test_field_formatter_third_party_settings_form]');
    $this->assertNotEmpty($field_third_party, 'The field added in hook_field_formatter_third_party_settings_form() is present on the settings form.');

    // Change the value and submit the form to save the third party settings.
    $field_third_party->setValue('foo');
    $page->findButton('Update')->click();
    $assert_session->assertExpectedAjaxRequest(2);
    $button_save->click();

    // Assert the third party settings.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    $this->drupalGet($manage_display);

    $id = 'node.' . $this->type . '.default';
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = $displayStorage->loadUnchanged($id);
    $this->assertEquals('foo', $display->getRenderer('field_test')->getThirdPartySetting('field_third_party_test', 'field_test_field_formatter_third_party_settings_form'));
    $this->assertContains('field_third_party_test', $display->calculateDependencies()->getDependencies()['module'], 'The display has a dependency on field_third_party_test module.');

    // Change the formatter to an empty setting and validate it's initialized
    // correctly.
    $field_test_format_type = $page->findField('fields[field_test][type]');
    $field_test_format_type->setValue('field_empty_setting');
    $assert_session->assertExpectedAjaxRequest(1);
    $assert_session->responseNotContains('Default empty setting now has a value.');
    $this->assertTrue($field_test_settings->isVisible());

    // Set the empty_setting option to a non-empty value again and validate
    // the formatting summary now display's this correctly.
    $field_test_settings->click();
    $assert_session->assertExpectedAjaxRequest(2);
    $field_empty_setting = $page->findField('fields[field_test][settings_edit_form][settings][field_empty_setting]');
    $field_empty_setting->setValue('non empty setting');
    $page->findButton('Update')->click();
    $assert_session->assertExpectedAjaxRequest(3);
    $assert_session->responseContains('Default empty setting now has a value.');

    // Test the settings form behavior. An edit button should be present since
    // there are third party settings to configure.
    $field_test_format_type->setValue('field_no_settings');
    $this->assertTrue($field_test_settings->isVisible());

    // Make sure we can save the third party settings when there are no settings
    // available.
    $field_test_settings->click();
    $assert_session->assertExpectedAjaxRequest(4);
    $page->findButton('Update')->click();

    // When a module providing third-party settings to a formatter (or widget)
    // is uninstalled, the formatter remains enabled but the provided settings,
    // together with the corresponding form elements, are removed from the
    // display component.
    \Drupal::service('module_installer')->uninstall(['field_third_party_test']);

    // Ensure the button is still there after the module has been disabled.
    $this->drupalGet($manage_display);
    $this->assertTrue($field_test_settings->isVisible());

    // Ensure that third-party form elements are not present anymore.
    $field_test_settings->click();
    $assert_session->assertExpectedAjaxRequest(1);
    $field_third_party = $page->findField('fields[field_test][settings_edit_form][third_party_settings][field_third_party_test][field_test_field_formatter_third_party_settings_form]');
    $this->assertEmpty($field_third_party);

    // Ensure that third-party settings were removed from the formatter.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = $displayStorage->loadUnchanged($display_id);
    $component = $display->getComponent('field_test');
    $this->assertArrayNotHasKey('field_third_party_test', $component['third_party_settings']);
  }

  /**
   * Tests widget settings.
   */
  public function testWidgetUI() {
    // Admin Manage Fields page.
    $manage_fields = 'admin/structure/types/manage/' . $this->type;
    // Admin Manage Display page.
    $manage_display = $manage_fields . '/form-display';

    $form_storage = $this->entityTypeManager->getStorage('entity_form_display');

    // Creates a new field that can be used with multiple formatters.
    // Reference: Drupal\field_test\Plugin\Field\FieldWidget\TestFieldWidgetMultiple::isApplicable().
    $this->fieldUIAddNewFieldJS($manage_fields, 'test', 'Test field');

    // Get the display options (formatter and settings) that were automatically
    // assigned for the 'default' display.
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
    $display = $form_storage->loadUnchanged("node.{$this->type}.default");
    $display_options = $display->getComponent('field_test');
    $widget_type = $display_options['type'];
    $default_settings = \Drupal::service('plugin.manager.field.widget')->getDefaultSettings($widget_type);
    $setting_name = key($default_settings);
    $setting_value = $display_options['settings'][$setting_name];

    // Display the "Manage form display" screen and check if the expected
    // widget is selected.
    $this->drupalGet($manage_display);

    $session = $this->getSession();
    $assert_session = $this->assertSession();
    $page = $session->getPage();

    $field_test_settings = $page->find('css', 'input[name="field_test_settings_edit"]');
    $field_test_type = $page->findField('fields[field_test][type]');
    $button_save = $page->findButton('Save');

    $this->assertEquals($widget_type, $field_test_type->getValue(), 'The expected widget is selected.');
    $assert_session->responseContains("$setting_name: $setting_value");

    // Check whether widget weights are respected.
    $this->assertFieldSelectOptions($field_test_type, [
      'test_field_widget',
      'test_field_widget_multilingual',
      'test_field_widget_multiple',
    ]);

    $field_test_type->setValue('test_field_widget_multiple');
    $assert_session->assertExpectedAjaxRequest(1);
    $button_save->click();

    $this->drupalGet($manage_display);
    $widget_type = 'test_field_widget_multiple';
    $default_settings = \Drupal::service('plugin.manager.field.widget')->getDefaultSettings($widget_type);
    $setting_name = key($default_settings);
    $setting_value = $default_settings[$setting_name];
    $this->assertEquals($widget_type, $field_test_type->getValue(), 'The expected widget is selected.');
    $assert_session->responseContains("$setting_name: $setting_value");
    $button_save->click();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
    $display = $form_storage->loadUnchanged("node.{$this->type}.default");
    $display_options = $display->getComponent('field_test');
    $current_widget = $display_options['type'];
    $current_setting_value = $display_options['settings'][$setting_name];
    $this->assertEquals($current_widget, $widget_type, 'The widget was updated.');
    $this->assertEquals($current_setting_value, $setting_value, 'The setting was updated.');

    // Assert that hook_field_widget_settings_summary_alter() is called.
    $assert_session->responseContains('field_test_field_widget_settings_summary_alter');

    $field_test_settings->click();
    $assert_session->assertExpectedAjaxRequest(1);

    // Assert that the field added in
    // field_test_field_widget_third_party_settings_form() is present.
    $field_third_party_test = $page->findField('fields[field_test][settings_edit_form][third_party_settings][field_third_party_test][field_test_widget_third_party_settings_form]');
    $this->assertNotEmpty($field_third_party_test, 'The field added in hook_field_widget_third_party_settings_form() is present on the settings form.');
    $field_third_party_test->setValue('foo');
    $page->findButton('Update')->click();
    $assert_session->assertWaitOnAjaxRequest();

    $button_save->click();
    $this->drupalGet($manage_display);

    // Assert the third party settings.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
    $display = $form_storage->loadUnchanged('node.' . $this->type . '.default');
    $this->assertEquals('foo', $display->getRenderer('field_test')->getThirdPartySetting('field_third_party_test', 'field_test_widget_third_party_settings_form'));
    $this->assertContains('field_third_party_test', $display->calculateDependencies()->getDependencies()['module'], 'Form display does not have a dependency on field_third_party_test module.');

    // Creates a new field that can not be used with the multiple formatter.
    // Reference: Drupal\field_test\Plugin\Field\FieldWidget\TestFieldWidgetMultiple::isApplicable().
    $this->fieldUIAddNewFieldJS($manage_fields, 'onewidgetfield', 'One Widget Field');

    // Go to the Manage Form Display.
    $this->drupalGet($manage_display);

    $field_onewidgetfield_type = $page->findField('fields[field_onewidgetfield][type]');
    $field_test_drag_handle = $page->find('css', '#field-test .tabledrag-handle');
    $field_region = $page->findField('fields[field_test][region]');
    $weight_toggle = $page->find('css', '.tabledrag-toggle-weight');
    $target = $page->find('css', '.region-hidden-message');

    // Checks if the select elements contain the specified options.
    $this->assertFieldSelectOptions($field_test_type, [
      'test_field_widget',
      'test_field_widget_multilingual',
      'test_field_widget_multiple',
    ]);
    $this->assertFieldSelectOptions($field_onewidgetfield_type, [
      'test_field_widget',
      'test_field_widget_multilingual',
    ]);

    $field_test_drag_handle->dragTo($target);
    $assert_session->assertWaitOnAjaxRequest();
    $button_save->click();

    // Validate the changed display settings on the server.
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
    $display = $form_storage->loadUnchanged("node.{$this->type}.default");
    $this->assertNull($display->getComponent('field_test'));

    // Switch to manual mode.
    $weight_toggle->click();

    // Change the region to content using the region field.
    $this->assertEquals('hidden', $field_region->getValue());
    $field_region->setValue('content');
    $button_save->click();

    // Validate the change on the server.
    $this->drupalGet($manage_display);
    $display = EntityFormDisplay::load("node.{$this->type}.default");
    $this->assertNotNull($display->getComponent('field_test'));
  }

  /**
   * Checks if a select element contains the specified options.
   *
   * @param \Behat\Mink\Element\NodeElement $field
   *   The select field to validate.
   * @param array $expected_options
   *   An array of expected options.
   * @param string|null $selected
   *   The default value to validate.
   *
   * @internal
   */
  protected function assertFieldSelectOptions(NodeElement $field, array $expected_options, ?string $selected = NULL): void {
    /** @var \Behat\Mink\Element\NodeElement[] $select_options */
    $select_options = $field->findAll('xpath', 'option');

    // Validate the number of options.
    $this->assertSameSize($expected_options, $select_options);

    // Validate the options and expected order.
    foreach ($select_options as $key => $option) {
      $this->assertEquals($option->getAttribute('value'), $expected_options[$key]);
    }

    // Validate the default value if passed.
    if (!is_null($selected)) {
      $this->assertEquals($selected, $field->getValue());
    }
  }

  /**
   * Confirms that notifications to save appear when necessary.
   */
  public function testNotAppliedUntilSavedWarning() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Admin Manage Fields page.
    $manage_fields = 'admin/structure/types/manage/' . $this->type;

    $this->fieldUIAddNewFieldJS($manage_fields, 'test', 'Test field');
    $manage_display = 'admin/structure/types/manage/' . $this->type . '/display';
    $manage_form = 'admin/structure/types/manage/' . $this->type . '/form-display';

    // Form display, change widget type.
    $this->drupalGet($manage_form);
    $assert_session->elementNotExists('css', '.tabledrag-changed-warning');
    $assert_session->elementNotExists('css', 'abbr.tabledrag-changed');
    $page->selectFieldOption('fields[uid][type]', 'options_buttons');
    $this->assertNotNull($changed_warning = $assert_session->waitForElementVisible('css', '.tabledrag-changed-warning'));
    $this->assertNotNull($assert_session->waitForElementVisible('css', ' #uid abbr.tabledrag-changed'));
    $this->assertSame('* You have unsaved changes.', $changed_warning->getText());

    // Form display, change widget settings.
    $this->drupalGet($manage_form);
    $edit_widget_button = $assert_session->waitForElementVisible('css', '[data-drupal-selector="edit-fields-uid-settings-edit"]');
    $edit_widget_button->press();
    $assert_session->waitForText('3rd party formatter settings form');

    // Confirm the AJAX operation of opening the form does not result in the row
    // being set as changed. New settings must be submitted for that to happen.
    $assert_session->elementNotExists('css', 'abbr.tabledrag-changed');
    $cancel_button = $assert_session->waitForElementVisible('css', '[data-drupal-selector="edit-fields-uid-settings-edit-form-actions-cancel-settings"]');
    $cancel_button->press();
    $assert_session->assertNoElementAfterWait('css', '[data-drupal-selector="edit-fields-uid-settings-edit-form-actions-cancel-settings"]');
    $assert_session->elementNotExists('css', '.tabledrag-changed-warning');
    $assert_session->elementNotExists('css', 'abbr.tabledrag-changed');
    $edit_widget_button = $assert_session->waitForElementVisible('css', '[data-drupal-selector="edit-fields-uid-settings-edit"]');
    $edit_widget_button->press();
    $widget_field = $assert_session->waitForField('fields[uid][settings_edit_form][third_party_settings][field_third_party_test][field_test_widget_third_party_settings_form]');
    $widget_field->setValue('honk');
    $update_button = $assert_session->waitForElementVisible('css', '[data-drupal-selector="edit-fields-uid-settings-edit-form-actions-save-settings"]');
    $update_button->press();
    $assert_session->assertNoElementAfterWait('css', '[data-drupal-selector="edit-fields-field-test-settings-edit-form-actions-cancel-settings"]');
    $this->assertNotNull($changed_warning = $assert_session->waitForElementVisible('css', '.tabledrag-changed-warning'));
    $this->assertNotNull($assert_session->waitForElementVisible('css', ' #uid abbr.tabledrag-changed'));
    $this->assertSame('* You have unsaved changes.', $changed_warning->getText());

    // Content display, change formatter type.
    $this->drupalGet($manage_display);
    $assert_session->elementNotExists('css', '.tabledrag-changed-warning');
    $assert_session->elementNotExists('css', 'abbr.tabledrag-changed');
    $page->selectFieldOption('edit-fields-field-test-label', 'inline');
    $this->assertNotNull($changed_warning = $assert_session->waitForElementVisible('css', '.tabledrag-changed-warning'));
    $this->assertNotNull($assert_session->waitForElementVisible('css', ' #field-test abbr.tabledrag-changed'));
    $this->assertSame('* You have unsaved changes.', $changed_warning->getText());

    // Content display, change formatter settings.
    $this->drupalGet($manage_display);
    $assert_session->elementNotExists('css', '.tabledrag-changed-warning');
    $assert_session->elementNotExists('css', 'abbr.tabledrag-changed');
    $edit_formatter_button = $assert_session->waitForElementVisible('css', '[data-drupal-selector="edit-fields-field-test-settings-edit"]');
    $edit_formatter_button->press();
    $assert_session->waitForText('3rd party formatter settings form');
    $cancel_button = $assert_session->waitForElementVisible('css', '[data-drupal-selector="edit-fields-field-test-settings-edit-form-actions-cancel-settings"]');
    $cancel_button->press();
    $assert_session->assertNoElementAfterWait('css', '[data-drupal-selector="edit-fields-field-test-settings-edit-form-actions-cancel-settings"]');
    $assert_session->elementNotExists('css', '.tabledrag-changed-warning');
    $assert_session->elementNotExists('css', 'abbr.tabledrag-changed');
    $edit_formatter_button = $assert_session->waitForElementVisible('css', '[data-drupal-selector="edit-fields-field-test-settings-edit"]');
    $edit_formatter_button->press();
    $formatter_field = $assert_session->waitForField('fields[field_test][settings_edit_form][third_party_settings][field_third_party_test][field_test_field_formatter_third_party_settings_form]');
    $formatter_field->setValue('honk');
    $update_button = $assert_session->waitForElementVisible('css', '[data-drupal-selector="edit-fields-field-test-settings-edit-form-actions-save-settings"]');
    $update_button->press();
    $assert_session->assertNoElementAfterWait('css', '[data-drupal-selector="edit-fields-field-test-settings-edit-form-actions-cancel-settings"]');
    $this->assertNotNull($changed_warning = $assert_session->waitForElementVisible('css', '.tabledrag-changed-warning'));
    $this->assertNotNull($assert_session->waitForElementVisible('css', ' #field-test abbr.tabledrag-changed'));
    $this->assertSame('* You have unsaved changes.', $changed_warning->getText());
  }

}
