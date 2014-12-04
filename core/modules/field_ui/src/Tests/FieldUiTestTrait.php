<?php

/**
 * @file
 * Contains \Drupal\field_ui\Tests\FieldUiTestTrait.
 */

namespace Drupal\field_ui\Tests;

/**
 * Provides common functionality for the Field UI test classes.
 */
trait FieldUiTestTrait {

  /**
   * Creates a new field through the Field UI.
   *
   * @param string $bundle_path
   *   Admin path of the bundle that the new field is to be attached to.
   * @param string $field_name
   *   The field name of the new field storage.
   * @param string $label
   *   (optional) The label of the new field. Defaults to a random string.
   * @param string $field_type
   *   (optional) The field type of the new field storage. Defaults to
   *   'test_field'.
   * @param array $storage_edit
   *   (optional) $edit parameter for drupalPostForm() on the second step
   *   ('Storage settings' form).
   * @param array $field_edit
   *   (optional) $edit parameter for drupalPostForm() on the third step ('Field
   *   settings' form).
   */
  public function fieldUIAddNewField($bundle_path, $field_name, $label = NULL, $field_type = 'test_field', array $storage_edit = array(), array $field_edit = array()) {
    $label = $label ?: $this->randomString();
    $initial_edit = array(
      'fields[_add_new_field][field_name]' => $field_name,
      'fields[_add_new_field][type]' => $field_type,
      'fields[_add_new_field][label]' => $label,
    );

    // Allow the caller to set a NULL path in case they navigated to the right
    // page before calling this method.
    if ($bundle_path !== NULL) {
      $bundle_path = "$bundle_path/fields";
    }

    // First step : 'Add new field' on the 'Manage fields' page.
    $this->drupalPostForm($bundle_path,  $initial_edit, t('Save'));
    $this->assertRaw(t('These settings apply to the %label field everywhere it is used.', array('%label' => $label)), 'Storage settings page was displayed.');
    // Test Breadcrumbs.
    $this->assertLink($label, 0, 'Field label is correct in the breadcrumb of the storage settings page.');

    // Second step : 'Storage settings' form.
    $this->drupalPostForm(NULL, $storage_edit, t('Save field settings'));
    $this->assertRaw(t('Updated field %label field settings.', array('%label' => $label)), 'Redirected to field settings page.');

    // Third step : 'Field settings' form.
    $this->drupalPostForm(NULL, $field_edit, t('Save settings'));
    $this->assertRaw(t('Saved %label configuration.', array('%label' => $label)), 'Redirected to "Manage fields" page.');

    // Check that the field appears in the overview form.
    $this->assertFieldByXPath('//table[@id="field-overview"]//tr/td[1]', $label, 'Field was created and appears in the overview page.');
  }

  /**
   * Adds an existing field through the Field UI.
   *
   * @param string $bundle_path
   *   Admin path of the bundle that the field is to be attached to.
   * @param string $existing_field_name
   *   The name of the existing field storage for which we want to add a new
   *   field.
   * @param string $label
   *   (optional) The label of the new field. Defaults to a random string.
   * @param array $field_edit
   *   (optional) $edit parameter for drupalPostForm() on the second step
   *   ('Field settings' form).
   */
  public function fieldUIAddExistingField($bundle_path, $existing_field_name, $label = NULL, array $field_edit = array()) {
    $label = $label ?: $this->randomString();
    $initial_edit = array(
      'fields[_add_existing_field][label]' => $label,
      'fields[_add_existing_field][field_name]' => $existing_field_name,
    );

    // First step : 'Re-use existing field' on the 'Manage fields' page.
    $this->drupalPostForm("$bundle_path/fields", $initial_edit, t('Save'));
    $this->assertNoRaw('&amp;lt;', 'The page does not have double escaped HTML tags.');

    // Second step : 'Field settings' form.
    $this->drupalPostForm(NULL, $field_edit, t('Save settings'));
    $this->assertRaw(t('Saved %label configuration.', array('%label' => $label)), 'Redirected to "Manage fields" page.');

    // Check that the field appears in the overview form.
    $this->assertFieldByXPath('//table[@id="field-overview"]//tr/td[1]', $label, 'Field was created and appears in the overview page.');
  }

  /**
   * Deletes a field through the Field UI.
   *
   * @param string $bundle_path
   *   Admin path of the bundle that the field is to be deleted from.
   * @param string $field_name
   *   The name of the field.
   * @param string $label
   *   The label of the field.
   * @param string $bundle_label
   *   The label of the bundle.
   */
  public function fieldUIDeleteField($bundle_path, $field_name, $label, $bundle_label) {
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
