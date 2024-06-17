<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\Traits;

use Behat\Mink\Exception\ElementNotFoundException;

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
   *   (optional) $edit parameter for submitForm() on the second step
   *   ('Storage settings' form).
   * @param array $field_edit
   *   (optional) $edit parameter for submitForm() on the third step ('Field
   *   settings' form).
   * @param bool $save_settings
   *   (optional) Parameter for conditional execution of second and third step
   *   (Saving the storage settings and field settings). Defaults to 'TRUE'.
   */
  public function fieldUIAddNewField($bundle_path, $field_name, $label = NULL, $field_type = 'test_field', array $storage_edit = [], array $field_edit = [], bool $save_settings = TRUE) {
    // Generate a label containing only letters and numbers to prevent random
    // test failure.
    // See https://www.drupal.org/project/drupal/issues/3030902
    $label = $label ?: $this->randomMachineName();
    $initial_edit = [
      'new_storage_type' => $field_type,
    ];
    $second_edit = [
      'label' => $label,
      'field_name' => $field_name,
    ];

    // Allow the caller to set a NULL path in case they navigated to the right
    // page before calling this method.
    if ($bundle_path !== NULL) {
      $bundle_path = "$bundle_path/fields/add-field";
      // First step: 'Add field' page.
      $this->drupalGet($bundle_path);
    }
    else {
      $bundle_path = $this->getUrl();
    }

    try {
      // First check if the passed in field type is not part of a group.
      $this->assertSession()->elementExists('css', "[name='new_storage_type'][value='$field_type']");
    }
    // If the element could not be found then it is probably in a group.
    catch (ElementNotFoundException) {
      // Call the helper function to confirm it is in a group.
      $field_group = $this->getFieldFromGroup($field_type);
      if ($field_group) {
        // Pass in the group name as the new storage type.
        $initial_edit['new_storage_type'] = $field_group;
        $second_edit['group_field_options_wrapper'] = $field_type;
        $this->drupalGet($bundle_path);
      }
    }
    $this->submitForm($initial_edit, 'Continue');
    $this->submitForm($second_edit, 'Continue');
    // Assert that the field is not created.
    $this->assertFieldDoesNotExist($bundle_path, $label);
    if ($save_settings) {
      $this->assertSession()->pageTextContains("These settings apply to the $label field everywhere it is used.");
      // Test Breadcrumbs.
      $this->getSession()->getPage()->findLink($label);

      // Ensure that each array key in $storage_edit is prefixed with field_storage.
      $prefixed_storage_edit = [];
      foreach ($storage_edit as $key => $value) {
        if (str_starts_with($key, 'field_storage')) {
          $prefixed_storage_edit[$key] = $value;
          continue;
        }
        // If the key starts with settings, it needs to be prefixed differently.
        if (str_starts_with($key, 'settings[')) {
          $prefixed_storage_edit[str_replace('settings[', 'field_storage[subform][settings][', $key)] = $value;
          continue;
        }
        $prefixed_storage_edit['field_storage[subform][' . $key . ']'] = $value;
      }

      // Second step: 'Storage settings' form.
      $this->submitForm($prefixed_storage_edit, 'Update settings');

      // Third step: 'Field settings' form.
      $this->submitForm($field_edit, 'Save settings');
      $this->assertSession()->pageTextContains("Saved $label configuration.");

      // Check that the field appears in the overview form.
      $this->assertFieldExistsOnOverview($label);
    }
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
   *   (optional) $edit parameter for submitForm() on the second step
   *   ('Field settings' form).
   */
  public function fieldUIAddExistingField($bundle_path, $existing_storage_name, $label = NULL, array $field_edit = []) {
    $label = $label ?: $this->randomMachineName();
    $field_edit['edit-label'] = $label;

    // First step: navigate to the re-use field page.
    $this->drupalGet("{$bundle_path}/fields/");
    // Confirm that the local action is visible.
    $this->assertSession()->linkExists('Re-use an existing field');
    $this->clickLink('Re-use an existing field');
    $this->assertSession()->elementExists('css', "input[value=Re-use][name=$existing_storage_name]");
    $this->click("input[value=Re-use][name=$existing_storage_name]");

    // Set the main content to only the content region because the label can
    // contain HTML which will be auto-escaped by Twig.
    $this->assertSession()->responseContains('field-config-edit-form');
    // Check that the page does not have double escaped HTML tags.
    $this->assertSession()->responseNotContains('&amp;lt;');

    // Second step: 'Field settings' form.
    $this->submitForm($field_edit, 'Save settings');
    $this->assertSession()->pageTextContains("Saved $label configuration.");

    // Check that the field appears in the overview form.
    $xpath = $this->assertSession()->buildXPathQuery("//table[@id=\"field-overview\"]//tr/td[1 and text() = :label]", [
      ':label' => $label,
    ]);
    $this->assertSession()->elementExists('xpath', $xpath);
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
   * @param string $source_label
   *   (optional) The label of the source entity type bundle.
   */
  public function fieldUIDeleteField($bundle_path, $field_name, $label, $bundle_label, string $source_label = '') {
    // Display confirmation form.
    $this->drupalGet("$bundle_path/fields/$field_name/delete");
    $this->assertSession()->pageTextContains("Are you sure you want to delete the field $label");

    // Test Breadcrumbs.
    $this->assertSession()->linkExists($label, 0, 'Field label is correct in the breadcrumb of the field delete page.');

    // Submit confirmation form.
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains("The field $label has been deleted from the $bundle_label $source_label");

    // Check that the field does not appear in the overview form.
    $xpath = $this->assertSession()->buildXPathQuery('//table[@id="field-overview"]//span[@class="label-field" and text()= :label]', [
      ':label' => $label,
    ]);
    $this->assertSession()->elementNotExists('xpath', $xpath);
  }

  /**
   * Helper function that returns the name of the group that a field is in.
   *
   * @param string $field_type
   *   The name of the field type.
   *
   * @return string
   *   Group name
   */
  public function getFieldFromGroup($field_type) {
    $group_elements = $this->getSession()->getPage()->findAll('css', '.field-option-radio');
    $groups = [];
    foreach ($group_elements as $group_element) {
      $groups[] = $group_element->getAttribute('value');
    }
    foreach ($groups as $group) {
      $test = [
        'new_storage_type' => $group,
      ];
      $this->submitForm($test, 'Continue');
      try {
        $this->assertSession()->elementExists('css', "[name='group_field_options_wrapper'][value='$field_type']");
        $this->submitForm([], 'Back');
        return $group;
      }
      catch (ElementNotFoundException) {
        $this->submitForm([], 'Back');
        continue;
      }
    }
    return NULL;
  }

  /**
   * Asserts that the field doesn't exist in the overview form.
   *
   * @param string $bundle_path
   *   The bundle path.
   * @param string $label
   *   The field label.
   */
  protected function assertFieldDoesNotExist(string $bundle_path, string $label) {
    $original_url = $this->getUrl();
    $this->drupalGet(explode('/fields', $bundle_path)[0] . '/fields');
    $this->assertFieldDoesNotExistOnOverview($label);
    $this->drupalGet($original_url);
  }

  /**
   * Asserts that the field appears on the overview form.
   *
   * @param string $label
   *   The field label.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function assertFieldExistsOnOverview(string $label) {
    $xpath = $this->assertSession()
      ->buildXPathQuery("//table[@id=\"field-overview\"]//tr/td[1 and text() = :label]", [
        ':label' => $label,
      ]);
    $element = $this->getSession()->getPage()->find('xpath', $xpath);
    if ($element === NULL) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'form field', 'label', $label);
    }
  }

  /**
   * Asserts that the field does not appear on the overview form.
   *
   * @param string $label
   *   The field label.
   */
  protected function assertFieldDoesNotExistOnOverview(string $label) {
    $xpath = $this->assertSession()
      ->buildXPathQuery("//table[@id=\"field-overview\"]//tr/td[1 and text() = :label]", [
        ':label' => $label,
      ]);
    $element = $this->getSession()->getPage()->find('xpath', $xpath);
    $this->assertSession()->assert($element === NULL, sprintf('A field "%s" appears on this page, but it should not.', $label));
  }

  /**
   * Asserts that a header cell appears on a table.
   *
   * @param string $table_id
   *   The HTML attribute value to target a given table.
   * @param string $label
   *   The cell label.
   */
  protected function assertTableHeaderExistsByLabel(string $table_id, string $label): void {
    $expression = '//table[@id=:id]//tr//th[1 and text() = :label]';
    $xpath = $this->assertSession()->buildXPathQuery($expression, [
      ':id' => $table_id,
      ':label' => $label,
    ]);
    $element = $this->getSession()->getPage()->find('xpath', $xpath);
    $this->assertSession()->assert($element !== NULL, sprintf('Table header not found by label: "%s".', $label));
  }

}
