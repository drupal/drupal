<?php

namespace Drupal\Tests\field_ui\Traits;

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
  public function fieldUIAddNewField($bundle_path, $field_name, $label = NULL, $field_type = 'test_field', array $storage_edit = [], array $field_edit = []) {
    // Generate a label containing only letters and numbers to prevent random
    // test failure.
    // See https://www.drupal.org/project/drupal/issues/3030902
    $label = $label ?: $this->randomMachineName();
    $initial_edit = [
      'new_storage_type' => $field_type,
      'label' => $label,
      'field_name' => $field_name,
    ];

    // Allow the caller to set a NULL path in case they navigated to the right
    // page before calling this method.
    if ($bundle_path !== NULL) {
      $bundle_path = "$bundle_path/fields/add-field";
    }

    // First step: 'Add field' page.
    $this->drupalPostForm($bundle_path, $initial_edit, t('Save and continue'));
    $this->assertRaw(t('These settings apply to the %label field everywhere it is used.', ['%label' => $label]), 'Storage settings page was displayed.');
    // Test Breadcrumbs.
    $this->assertSession()->linkExists($label, 0, 'Field label is correct in the breadcrumb of the storage settings page.');

    // Second step: 'Storage settings' form.
    $this->drupalPostForm(NULL, $storage_edit, t('Save field settings'));
    $this->assertRaw(t('Updated field %label field settings.', ['%label' => $label]), 'Redirected to field settings page.');

    // Third step: 'Field settings' form.
    $this->drupalPostForm(NULL, $field_edit, t('Save settings'));
    $this->assertRaw(t('Saved %label configuration.', ['%label' => $label]), 'Redirected to "Manage fields" page.');

    // Check that the field appears in the overview form.
    $this->assertFieldByXPath('//table[@id="field-overview"]//tr/td[1]', $label, 'Field was created and appears in the overview page.');
  }

  /**
   * Adds an existing field through the Field UI.
   *
   * @param string $bundle_path
   *   Admin path of the bundle that the field is to be attached to.
   * @param string $existing_storage_name
   *   The name of the existing field storage for which we want to add a new
   *   field.
   * @param string $label
   *   (optional) The label of the new field. Defaults to a random string.
   * @param array $field_edit
   *   (optional) $edit parameter for drupalPostForm() on the second step
   *   ('Field settings' form).
   */
  public function fieldUIAddExistingField($bundle_path, $existing_storage_name, $label = NULL, array $field_edit = []) {
    $label = $label ?: $this->randomString();
    $initial_edit = [
      'existing_storage_name' => $existing_storage_name,
      'existing_storage_label' => $label,
    ];

    // First step: 'Re-use existing field' on the 'Add field' page.
    $this->drupalPostForm("$bundle_path/fields/add-field", $initial_edit, t('Save and continue'));
    // Set the main content to only the content region because the label can
    // contain HTML which will be auto-escaped by Twig.
    $this->assertRaw('field-config-edit-form', 'The field config edit form is present.');
    $this->assertNoRaw('&amp;lt;', 'The page does not have double escaped HTML tags.');

    // Second step: 'Field settings' form.
    $this->drupalPostForm(NULL, $field_edit, t('Save settings'));
    $this->assertRaw(t('Saved %label configuration.', ['%label' => $label]), 'Redirected to "Manage fields" page.');

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
    $this->assertRaw(t('Are you sure you want to delete the field %label', ['%label' => $label]), 'Delete confirmation was found.');

    // Test Breadcrumbs.
    $this->assertSession()->linkExists($label, 0, 'Field label is correct in the breadcrumb of the field delete page.');

    // Submit confirmation form.
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertRaw(t('The field %label has been deleted from the %type content type.', ['%label' => $label, '%type' => $bundle_label]), 'Delete message was found.');

    // Check that the field does not appear in the overview form.
    $this->assertNoFieldByXPath('//table[@id="field-overview"]//span[@class="label-field"]', $label, 'Field does not appear in the overview page.');
  }

}
