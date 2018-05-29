<?php

namespace Drupal\field_ui\Tests;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\WebTestBase;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the Field UI "Manage display" and "Manage form display" screens.
 *
 * @group field_ui
 */
class ManageDisplayTest extends WebTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['node', 'field_ui', 'taxonomy', 'search', 'field_test', 'field_third_party_test', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');

    // Create a test user.
    $admin_user = $this->drupalCreateUser(['access content', 'administer content types', 'administer node fields', 'administer node form display', 'administer node display', 'administer taxonomy', 'administer taxonomy_term fields', 'administer taxonomy_term display', 'administer users', 'administer account settings', 'administer user display', 'bypass node access']);
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $type_name = strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType(['name' => $type_name, 'type' => $type_name]);
    $this->type = $type->id();

    // Create a default vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => mb_strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'help' => '',
      'nodes' => ['article' => 'article'],
      'weight' => mt_rand(0, 10),
    ]);
    $vocabulary->save();
    $this->vocabulary = $vocabulary->id();
  }

  /**
   * Tests formatter settings.
   */
  public function testFormatterUI() {
    $manage_fields = 'admin/structure/types/manage/' . $this->type;
    $manage_display = $manage_fields . '/display';

    // Create a field, and a node with some data for the field.
    $this->fieldUIAddNewField($manage_fields, 'test', 'Test field');

    // Get the display options (formatter and settings) that were automatically
    // assigned for the 'default' display.
    $display = entity_get_display('node', $this->type, 'default');
    $display_options = $display->getComponent('field_test');
    $format = $display_options['type'];
    $default_settings = \Drupal::service('plugin.manager.field.formatter')->getDefaultSettings($format);
    $setting_name = key($default_settings);
    $setting_value = $display_options['settings'][$setting_name];

    // Display the "Manage display" screen and check that the expected formatter
    // is selected.
    $this->drupalGet($manage_display);
    $this->assertFieldByName('fields[field_test][type]', $format, 'The expected formatter is selected.');
    $this->assertText("$setting_name: $setting_value", 'The expected summary is displayed.');

    // Check whether formatter weights are respected.
    $result = $this->xpath('//select[@id=:id]/option', [':id' => 'edit-fields-field-test-type']);
    $options = array_map(function ($item) {
      return (string) $item->attributes()->value[0];
    }, $result);
    $expected_options = [
      'field_no_settings',
      'field_empty_test',
      'field_empty_setting',
      'field_test_default',
      'field_test_multiple',
      'field_test_with_prepare_view',
      'field_test_applicable',
    ];
    $this->assertEqual($options, $expected_options, 'The expected formatter ordering is respected.');

    // Ensure that fields can be hidden directly by changing the region.
    $this->drupalGet($manage_display);
    $this->assertFieldByName('fields[field_test][region]', 'content');
    $edit = ['fields[field_test][region]' => 'hidden'];
    $this->drupalPostForm($manage_display, $edit, t('Save'));
    $this->assertFieldByName('fields[field_test][region]', 'hidden');
    $display = EntityViewDisplay::load("node.{$this->type}.default");
    $this->assertNull($display->getComponent('field_test'));

    // Restore the field to the content region.
    $edit = [
      'fields[field_test][type]' => 'field_test_default',
      'fields[field_test][region]' => 'content',
    ];
    $this->drupalPostForm($manage_display, $edit, t('Save'));

    // Change the formatter and check that the summary is updated.
    $edit = [
      'fields[field_test][type]' => 'field_test_multiple',
      'fields[field_test][region]' => 'content',
      'refresh_rows' => 'field_test',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, ['op' => t('Refresh')]);
    $format = 'field_test_multiple';
    $default_settings = \Drupal::service('plugin.manager.field.formatter')->getDefaultSettings($format);
    $setting_name = key($default_settings);
    $setting_value = $default_settings[$setting_name];
    $this->assertFieldByName('fields[field_test][type]', $format, 'The expected formatter is selected.');
    $this->assertText("$setting_name: $setting_value", 'The expected summary is displayed.');

    // Submit the form and check that the display is updated.
    $this->drupalPostForm(NULL, [], t('Save'));
    $display = entity_get_display('node', $this->type, 'default');
    $display_options = $display->getComponent('field_test');
    $current_format = $display_options['type'];
    $current_setting_value = $display_options['settings'][$setting_name];
    $this->assertEqual($current_format, $format, 'The formatter was updated.');
    $this->assertEqual($current_setting_value, $setting_value, 'The setting was updated.');

    // Assert that hook_field_formatter_settings_summary_alter() is called.
    $this->assertText('field_test_field_formatter_settings_summary_alter');

    // Click on the formatter settings button to open the formatter settings
    // form.
    $this->drupalPostAjaxForm(NULL, [], "field_test_settings_edit");

    // Assert that the field added in
    // field_test_field_formatter_third_party_settings_form() is present.
    $fieldname = 'fields[field_test][settings_edit_form][third_party_settings][field_third_party_test][field_test_field_formatter_third_party_settings_form]';
    $this->assertField($fieldname, 'The field added in hook_field_formatter_third_party_settings_form() is present on the settings form.');
    $edit = [$fieldname => 'foo'];
    $this->drupalPostAjaxForm(NULL, $edit, "field_test_plugin_settings_update");

    // Save the form to save the third party settings.
    $this->drupalPostForm(NULL, [], t('Save'));

    \Drupal::entityManager()->clearCachedFieldDefinitions();
    $id = 'node.' . $this->type . '.default';
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_view_display');
    $storage->resetCache([$id]);
    $display = $storage->load($id);
    $this->assertEqual($display->getRenderer('field_test')->getThirdPartySetting('field_third_party_test', 'field_test_field_formatter_third_party_settings_form'), 'foo');
    $this->assertTrue(in_array('field_third_party_test', $display->calculateDependencies()->getDependencies()['module']), 'The display has a dependency on field_third_party_test module.');

    // Confirm that the third party settings are not updated on the settings form.
    $this->drupalPostAjaxForm(NULL, [], "field_test_settings_edit");
    $this->assertFieldByName($fieldname, '');

    // Test the empty setting formatter.
    $edit = [
      'fields[field_test][type]' => 'field_empty_setting',
      'fields[field_test][region]' => 'content',
      ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertNoText('Default empty setting now has a value.');
    $this->assertFieldById('edit-fields-field-test-settings-edit');
    $this->drupalPostAjaxForm(NULL, [], "field_test_settings_edit");
    $fieldname = 'fields[field_test][settings_edit_form][settings][field_empty_setting]';
    $edit = [$fieldname => 'non empty setting'];
    $this->drupalPostAjaxForm(NULL, $edit, "field_test_plugin_settings_update");
    $this->assertText('Default empty setting now has a value.');

    // Test the settings form behavior. An edit button should be present since
    // there are third party settings to configure.
    $edit = [
      'fields[field_test][type]' => 'field_no_settings',
      'fields[field_test][region]' => 'content',
      'refresh_rows' => 'field_test',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, ['op' => t('Refresh')]);
    $this->assertFieldByName('field_test_settings_edit');

    // Make sure we can save the third party settings when there are no settings available
    $this->drupalPostAjaxForm(NULL, [], "field_test_settings_edit");
    $this->drupalPostAjaxForm(NULL, $edit, "field_test_plugin_settings_update");

    // When a module providing third-party settings to a formatter (or widget)
    // is uninstalled, the formatter remains enabled but the provided settings,
    // together with the corresponding form elements, are removed from the
    // display component.
    \Drupal::service('module_installer')->uninstall(['field_third_party_test']);

    // Ensure the button is still there after the module has been disabled.
    $this->drupalGet($manage_display);
    $this->assertResponse(200);
    $this->assertFieldByName('field_test_settings_edit');

    // Ensure that third-party form elements are not present anymore.
    $this->drupalPostAjaxForm(NULL, [], 'field_test_settings_edit');
    $fieldname = 'fields[field_test][settings_edit_form][third_party_settings][field_third_party_test][field_test_field_formatter_third_party_settings_form]';
    $this->assertNoField($fieldname);

    // Ensure that third-party settings were removed from the formatter.
    $display = EntityViewDisplay::load("node.{$this->type}.default");
    $component = $display->getComponent('field_test');
    $this->assertFalse(array_key_exists('field_third_party_test', $component['third_party_settings']));
  }

  /**
   * Tests widget settings.
   */
  public function testWidgetUI() {
    // Admin Manage Fields page.
    $manage_fields = 'admin/structure/types/manage/' . $this->type;
    // Admin Manage Display page.
    $manage_display = $manage_fields . '/form-display';

    // Creates a new field that can be used with multiple formatters.
    // Reference: Drupal\field_test\Plugin\Field\FieldWidget\TestFieldWidgetMultiple::isApplicable().
    $this->fieldUIAddNewField($manage_fields, 'test', 'Test field');

    // Get the display options (formatter and settings) that were automatically
    // assigned for the 'default' display.
    $display = entity_get_form_display('node', $this->type, 'default');
    $display_options = $display->getComponent('field_test');
    $widget_type = $display_options['type'];
    $default_settings = \Drupal::service('plugin.manager.field.widget')->getDefaultSettings($widget_type);
    $setting_name = key($default_settings);
    $setting_value = $display_options['settings'][$setting_name];

    // Display the "Manage form display" screen and check if the expected
    // widget is selected.
    $this->drupalGet($manage_display);
    $this->assertFieldByName('fields[field_test][type]', $widget_type, 'The expected widget is selected.');
    $this->assertText("$setting_name: $setting_value", 'The expected summary is displayed.');

    // Check whether widget weights are respected.
    $result = $this->xpath('//select[@id=:id]/option', [':id' => 'edit-fields-field-test-type']);
    $options = array_map(function ($item) {
      return (string) $item->attributes()->value[0];
    }, $result);
    $expected_options = [
      'test_field_widget',
      'test_field_widget_multilingual',
      'test_field_widget_multiple',
    ];
    $this->assertEqual($options, $expected_options, 'The expected widget ordering is respected.');

    // Change the widget and check that the summary is updated.
    $edit = [
      'fields[field_test][type]' => 'test_field_widget_multiple',
      'fields[field_test][region]' => 'content',
      'refresh_rows' => 'field_test',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, ['op' => t('Refresh')]);
    $widget_type = 'test_field_widget_multiple';
    $default_settings = \Drupal::service('plugin.manager.field.widget')->getDefaultSettings($widget_type);
    $setting_name = key($default_settings);
    $setting_value = $default_settings[$setting_name];
    $this->assertFieldByName('fields[field_test][type]', $widget_type, 'The expected widget is selected.');
    $this->assertText("$setting_name: $setting_value", 'The expected summary is displayed.');

    // Submit the form and check that the display is updated.
    $this->drupalPostForm(NULL, [], t('Save'));
    $display = entity_get_form_display('node', $this->type, 'default');
    $display_options = $display->getComponent('field_test');
    $current_widget = $display_options['type'];
    $current_setting_value = $display_options['settings'][$setting_name];
    $this->assertEqual($current_widget, $widget_type, 'The widget was updated.');
    $this->assertEqual($current_setting_value, $setting_value, 'The setting was updated.');

    // Assert that hook_field_widget_settings_summary_alter() is called.
    $this->assertText('field_test_field_widget_settings_summary_alter');

    // Click on the widget settings button to open the widget settings form.
    $this->drupalPostAjaxForm(NULL, [], "field_test_settings_edit");

    // Assert that the field added in
    // field_test_field_widget_third_party_settings_form() is present.
    $fieldname = 'fields[field_test][settings_edit_form][third_party_settings][field_third_party_test][field_test_widget_third_party_settings_form]';
    $this->assertField($fieldname, 'The field added in hook_field_widget_third_party_settings_form() is present on the settings form.');
    $edit = [$fieldname => 'foo'];
    $this->drupalPostAjaxForm(NULL, $edit, "field_test_plugin_settings_update");

    // Save the form to save the third party settings.
    $this->drupalPostForm(NULL, [], t('Save'));
    \Drupal::entityManager()->clearCachedFieldDefinitions();
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_form_display');
    $storage->resetCache(['node.' . $this->type . '.default']);
    $display = $storage->load('node.' . $this->type . '.default');
    $this->assertEqual($display->getRenderer('field_test')->getThirdPartySetting('field_third_party_test', 'field_test_widget_third_party_settings_form'), 'foo');
    $this->assertTrue(in_array('field_third_party_test', $display->calculateDependencies()->getDependencies()['module']), 'Form display does not have a dependency on field_third_party_test module.');

    // Confirm that the third party settings are not updated on the settings form.
    $this->drupalPostAjaxForm(NULL, [], "field_test_settings_edit");
    $this->assertFieldByName($fieldname, '');

    // Creates a new field that can not be used with the multiple formatter.
    // Reference: Drupal\field_test\Plugin\Field\FieldWidget\TestFieldWidgetMultiple::isApplicable().
    $this->fieldUIAddNewField($manage_fields, 'onewidgetfield', 'One Widget Field');

    // Go to the Manage Form Display.
    $this->drupalGet($manage_display);

    // Checks if the select elements contain the specified options.
    $this->assertFieldSelectOptions('fields[field_test][type]', ['test_field_widget', 'test_field_widget_multilingual', 'test_field_widget_multiple']);
    $this->assertFieldSelectOptions('fields[field_onewidgetfield][type]', ['test_field_widget', 'test_field_widget_multilingual']);

    // Ensure that fields can be hidden directly by changing the region.
    $this->assertFieldByName('fields[field_test][region]', 'content');
    $edit = ['fields[field_test][region]' => 'hidden'];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldByName('fields[field_test][region]', 'hidden');
    $display = EntityFormDisplay::load("node.{$this->type}.default");
    $this->assertNull($display->getComponent('field_test'));
  }

  /**
   * Tests switching view modes to use custom or 'default' settings'.
   */
  public function testViewModeCustom() {
    // Create a field, and a node with some data for the field.
    $this->fieldUIAddNewField('admin/structure/types/manage/' . $this->type, 'test', 'Test field');
    \Drupal::entityManager()->clearCachedFieldDefinitions();
    // For this test, use a formatter setting value that is an integer unlikely
    // to appear in a rendered node other than as part of the field being tested
    // (for example, unlikely to be part of the "Submitted by ... on ..." line).
    $value = 12345;
    $settings = [
      'type' => $this->type,
      'field_test' => [['value' => $value]],
    ];
    $node = $this->drupalCreateNode($settings);

    // Gather expected output values with the various formatters.
    $formatter_plugin_manager = \Drupal::service('plugin.manager.field.formatter');
    $field_test_default_settings = $formatter_plugin_manager->getDefaultSettings('field_test_default');
    $field_test_with_prepare_view_settings = $formatter_plugin_manager->getDefaultSettings('field_test_with_prepare_view');
    $output = [
      'field_test_default' => $field_test_default_settings['test_formatter_setting'] . '|' . $value,
      'field_test_with_prepare_view' => $field_test_with_prepare_view_settings['test_formatter_setting_additional'] . '|' . $value . '|' . ($value + 1),
    ];

    // Check that the field is displayed with the default formatter in 'rss'
    // mode (uses 'default'), and hidden in 'teaser' mode (uses custom settings).
    $this->assertNodeViewText($node, 'rss', $output['field_test_default'], "The field is displayed as expected in view modes that use 'default' settings.");
    $this->assertNodeViewNoText($node, 'teaser', $value, "The field is hidden in view modes that use custom settings.");

    // Change formatter for 'default' mode, check that the field is displayed
    // accordingly in 'rss' mode.
    $edit = [
      'fields[field_test][type]' => 'field_test_with_prepare_view',
      'fields[field_test][region]' => 'content',
    ];
    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/display', $edit, t('Save'));
    $this->assertNodeViewText($node, 'rss', $output['field_test_with_prepare_view'], "The field is displayed as expected in view modes that use 'default' settings.");

    // Specialize the 'rss' mode, check that the field is displayed the same.
    $edit = [
      "display_modes_custom[rss]" => TRUE,
    ];
    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/display', $edit, t('Save'));
    $this->assertNodeViewText($node, 'rss', $output['field_test_with_prepare_view'], "The field is displayed as expected in newly specialized 'rss' mode.");

    // Set the field to 'hidden' in the view mode, check that the field is
    // hidden.
    $edit = [
      'fields[field_test][region]' => 'hidden',
    ];
    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/display/rss', $edit, t('Save'));
    $this->assertNodeViewNoText($node, 'rss', $value, "The field is hidden in 'rss' mode.");

    // Set the view mode back to 'default', check that the field is displayed
    // accordingly.
    $edit = [
      "display_modes_custom[rss]" => FALSE,
    ];
    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/display', $edit, t('Save'));
    $this->assertNodeViewText($node, 'rss', $output['field_test_with_prepare_view'], "The field is displayed as expected when 'rss' mode is set back to 'default' settings.");

    // Specialize the view mode again.
    $edit = [
      "display_modes_custom[rss]" => TRUE,
    ];
    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/display', $edit, t('Save'));
    // Check that the previous settings for the view mode have been kept.
    $this->assertNodeViewNoText($node, 'rss', $value, "The previous settings are kept when 'rss' mode is specialized again.");
  }

  /**
   * Tests the local tasks are displayed correctly for view modes.
   */
  public function testViewModeLocalTasks() {
    $manage_display = 'admin/structure/types/manage/' . $this->type . '/display';
    $this->drupalGet($manage_display);
    $this->assertNoLink('Full content');
    $this->assertLink('Teaser');

    $this->drupalGet($manage_display . '/teaser');
    $this->assertNoLink('Full content');
    $this->assertLink('Default');
  }

  /**
   * Tests that fields with no explicit display settings do not break.
   */
  public function testNonInitializedFields() {
    // Create a test field.
    $this->fieldUIAddNewField('admin/structure/types/manage/' . $this->type, 'test', 'Test');

    // Check that the field appears as 'hidden' on the 'Manage display' page
    // for the 'teaser' mode.
    $this->drupalGet('admin/structure/types/manage/' . $this->type . '/display/teaser');
    $this->assertFieldByName('fields[field_test][region]', 'hidden', 'The field is displayed as \'hidden \'.');
  }

  /**
   * Tests hiding the view modes fieldset when there's only one available.
   */
  public function testSingleViewMode() {
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary . '/display');
    $this->assertNoText('Use custom display settings for the following view modes', 'Custom display settings fieldset found.');

    // This may not trigger a notice when 'view_modes_custom' isn't available.
    $this->drupalPostForm('admin/structure/taxonomy/manage/' . $this->vocabulary . '/overview/display', [], t('Save'));
  }

  /**
   * Tests that a message is shown when there are no fields.
   */
  public function testNoFieldsDisplayOverview() {
    // Create a fresh content type without any fields.
    NodeType::create([
      'type' => 'no_fields',
      'name' => 'No fields',
    ])->save();

    $this->drupalGet('admin/structure/types/manage/no_fields/display');
    $this->assertRaw(t('There are no fields yet added. You can add new fields on the <a href=":link">Manage fields</a> page.', [':link' => \Drupal::url('entity.node.field_ui_fields', ['node_type' => 'no_fields'])]));
  }

  /**
   * Asserts that a string is found in the rendered node in a view mode.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node.
   * @param $view_mode
   *   The view mode in which the node should be displayed.
   * @param $text
   *   Plain text to look for.
   * @param $message
   *   Message to display.
   *
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  public function assertNodeViewText(EntityInterface $node, $view_mode, $text, $message) {
    return $this->assertNodeViewTextHelper($node, $view_mode, $text, $message, FALSE);
  }

  /**
   * Asserts that a string is not found in the rendered node in a view mode.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node.
   * @param $view_mode
   *   The view mode in which the node should be displayed.
   * @param $text
   *   Plain text to look for.
   * @param $message
   *   Message to display.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  public function assertNodeViewNoText(EntityInterface $node, $view_mode, $text, $message) {
    return $this->assertNodeViewTextHelper($node, $view_mode, $text, $message, TRUE);
  }

  /**
   * Asserts that a string is (not) found in the rendered nodein a view mode.
   *
   * This helper function is used by assertNodeViewText() and
   * assertNodeViewNoText().
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node.
   * @param $view_mode
   *   The view mode in which the node should be displayed.
   * @param $text
   *   Plain text to look for.
   * @param $message
   *   Message to display.
   * @param $not_exists
   *   TRUE if this text should not exist, FALSE if it should.
   *
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  public function assertNodeViewTextHelper(EntityInterface $node, $view_mode, $text, $message, $not_exists) {
    // Make sure caches on the tester side are refreshed after changes
    // submitted on the tested side.
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Save current content so that we can restore it when we're done.
    $old_content = $this->getRawContent();

    // Render a cloned node, so that we do not alter the original.
    $clone = clone $node;
    $element = node_view($clone, $view_mode);
    $output = \Drupal::service('renderer')->renderRoot($element);
    $this->verbose(t('Rendered node - view mode: @view_mode', ['@view_mode' => $view_mode]) . '<hr />' . $output);

    // Assign content so that WebTestBase functions can be used.
    $this->setRawContent($output);
    $method = ($not_exists ? 'assertNoText' : 'assertText');
    $return = $this->{$method}((string) $text, $message);

    // Restore previous content.
    $this->setRawContent($old_content);

    return $return;
  }

  /**
   * Checks if a select element contains the specified options.
   *
   * @param string $name
   *   The field name.
   * @param array $expected_options
   *   An array of expected options.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertFieldSelectOptions($name, array $expected_options) {
    $xpath = $this->buildXPathQuery('//select[@name=:name]', [':name' => $name]);
    $fields = $this->xpath($xpath);
    if ($fields) {
      $field = $fields[0];
      $options = $this->getAllOptionsList($field);

      sort($options);
      sort($expected_options);

      return $this->assertIdentical($options, $expected_options);
    }
    else {
      return $this->fail('Unable to find field ' . $name);
    }
  }

  /**
   * Extracts all options from a select element.
   *
   * @param \SimpleXMLElement $element
   *   The select element field information.
   *
   * @return array
   *   An array of option values as strings.
   */
  protected function getAllOptionsList(\SimpleXMLElement $element) {
    $options = [];
    // Add all options items.
    foreach ($element->option as $option) {
      $options[] = (string) $option['value'];
    }

    // Loops trough all the option groups
    foreach ($element->optgroup as $optgroup) {
      $options = array_merge($this->getAllOptionsList($optgroup), $options);
    }

    return $options;
  }

}
