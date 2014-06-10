<?php

/**
 * @file
 * Contains \Drupal\field_ui\Tests\FieldUiTestBase.
 */

namespace Drupal\field_ui\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Provides common functionality for the Field UI test classes.
 */
abstract class FieldUiTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'field_ui', 'field_test', 'taxonomy', 'image');

  function setUp() {
    parent::setUp();

    // Create test user.
    $admin_user = $this->drupalCreateUser(array('access content', 'administer content types', 'administer node fields', 'administer node form display', 'administer node display', 'administer taxonomy', 'administer taxonomy_term fields', 'administer taxonomy_term display', 'administer users', 'administer account settings', 'administer user display', 'bypass node access'));
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $type_name = strtolower($this->randomName(8)) . '_test';
    $type = $this->drupalCreateContentType(array('name' => $type_name, 'type' => $type_name));
    $this->type = $type->type;

    // Create a default vocabulary.
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->randomName(),
      'description' => $this->randomName(),
      'vid' => drupal_strtolower($this->randomName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'help' => '',
      'nodes' => array('article' => 'article'),
      'weight' => mt_rand(0, 10),
    ));
    $vocabulary->save();
    $this->vocabulary = $vocabulary->id();
  }

  /**
   * Creates a new field through the Field UI.
   *
   * @param $bundle_path
   *   Admin path of the bundle that the new field is to be attached to.
   * @param $initial_edit
   *   $edit parameter for drupalPostForm() on the first step ('Manage fields'
   *   screen).
   * @param $field_edit
   *   $edit parameter for drupalPostForm() on the second step ('Field settings'
   *   form).
   * @param $instance_edit
   *   $edit parameter for drupalPostForm() on the third step ('Instance settings'
   *   form).
   */
  function fieldUIAddNewField($bundle_path, $initial_edit, $field_edit = array(), $instance_edit = array()) {
    // Use 'test_field' field type by default.
    $initial_edit += array(
      'fields[_add_new_field][type]' => 'test_field',
    );
    $label = $initial_edit['fields[_add_new_field][label]'];

    // First step : 'Add new field' on the 'Manage fields' page.
    $this->drupalPostForm("$bundle_path/fields",  $initial_edit, t('Save'));
    $this->assertRaw(t('These settings apply to the %label field everywhere it is used.', array('%label' => $label)), 'Field settings page was displayed.');
    // Test Breadcrumbs.
    $this->assertLink($label, 0, 'Field label is correct in the breadcrumb of the field settings page.');

    // Second step : 'Field settings' form.
    $this->drupalPostForm(NULL, $field_edit, t('Save field settings'));
    $this->assertRaw(t('Updated field %label field settings.', array('%label' => $label)), 'Redirected to instance and widget settings page.');

    // Third step : 'Instance settings' form.
    $this->drupalPostForm(NULL, $instance_edit, t('Save settings'));
    $this->assertRaw(t('Saved %label configuration.', array('%label' => $label)), 'Redirected to "Manage fields" page.');

    // Check that the field appears in the overview form.
    $this->assertFieldByXPath('//table[@id="field-overview"]//tr/td[1]', $label, 'Field was created and appears in the overview page.');
  }

  /**
   * Adds an existing field through the Field UI.
   *
   * @param $bundle_path
   *   Admin path of the bundle that the field is to be attached to.
   * @param $initial_edit
   *   $edit parameter for drupalPostForm() on the first step ('Manage fields'
   *   screen).
   * @param $instance_edit
   *   $edit parameter for drupalPostForm() on the second step ('Instance settings'
   *   form).
   */
  function fieldUIAddExistingField($bundle_path, $initial_edit, $instance_edit = array()) {
    $label = $initial_edit['fields[_add_existing_field][label]'];

    // First step : 'Re-use existing field' on the 'Manage fields' page.
    $this->drupalPostForm("$bundle_path/fields", $initial_edit, t('Save'));

    // Second step : 'Instance settings' form.
    $this->drupalPostForm(NULL, $instance_edit, t('Save settings'));
    $this->assertRaw(t('Saved %label configuration.', array('%label' => $label)), 'Redirected to "Manage fields" page.');

    // Check that the field appears in the overview form.
    $this->assertFieldByXPath('//table[@id="field-overview"]//tr/td[1]', $label, 'Field was created and appears in the overview page.');
  }

  /**
   * Deletes a field instance through the Field UI.
   *
   * @param $bundle_path
   *   Admin path of the bundle that the field instance is to be deleted from.
   * @param $field_name
   *   The name of the field.
   * @param $label
   *   The label of the field.
   * @param $bundle_label
   *   The label of the bundle.
   */
  function fieldUIDeleteField($bundle_path, $field_name, $label, $bundle_label) {
    // Display confirmation form.
    $this->drupalGet("$bundle_path/fields/$field_name/delete");
    $this->assertRaw(t('Are you sure you want to delete the field %label', array('%label' => $label)), 'Delete confirmation was found.');

    // Test Breadcrumbs.
    $this->assertLink($label, 0, 'Field label is correct in the breadcrumb of the field delete page.');

    // Submit confirmation form.
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertRaw(t('The field %label has been deleted from the %type content type.', array('%label' => $label, '%type' => $bundle_label)), 'Delete message was found.');

    // Check that the field does not appear in the overview form.
    $this->assertNoFieldByXPath('//table[@id="field-overview"]//span[@class="label-field"]', $label, 'Field does not appear in the overview page.');
  }
}
