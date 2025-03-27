<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\Traits;

use Behat\Mink\Exception\ElementNotFoundException;

/**
 * Provides common functionality for the Field UI tests that depend on JS.
 */
trait FieldUiJSTestTrait {
  use FieldUiTestTrait;

  /**
   * Creates a new field through the Field UI.
   *
   * @param string|null $bundle_path
   *   Admin path of the bundle that the new field is to be attached to.
   * @param string $field_name
   *   The field name of the new field storage.
   * @param string|null $label
   *   (optional) The label of the new field. Defaults to a random string.
   * @param string $field_type
   *   (optional) The field type of the new field storage. Defaults to
   *   'test_field'.
   * @param bool $save_settings
   *   (optional) Parameter for conditional execution of second and third step
   *   (Saving the storage settings and field settings). Defaults to 'TRUE'.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function fieldUIAddNewFieldJS(?string $bundle_path, string $field_name, ?string $label = NULL, string $field_type = 'test_field', bool $save_settings = TRUE): void {
    $this->getSession()->resizeWindow(1200, 800);
    $label = $label ?: $field_name;

    // Allow the caller to set a NULL path in case they navigated to the right
    // page before calling this method.
    if ($bundle_path !== NULL) {
      $bundle_path = "$bundle_path/fields";
      $this->drupalGet($bundle_path);
      $this->getSession()->getPage()->clickLink('Create a new field');
      $this->assertSession()->assertWaitOnAjaxRequest();
    }

    // First step: 'Add field' page.
    $session = $this->getSession();

    $page = $session->getPage();
    $assert_session = $this->assertSession();

    try {
      /** @var \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager */
      $field_type_plugin_manager = \Drupal::service('plugin.manager.field.field_type');
      $field_definitions = $field_type_plugin_manager->getUiDefinitions();
      $field_type_label = (string) $field_definitions[$field_type]['label'];
      $this->getSession()->getPage()->clickLink($field_type_label);
      $this->assertSession()->assertWaitOnAjaxRequest();

      if ($this->getSession()->getPage()->hasField('field_options_wrapper')) {
        $this->assertSession()->fieldExists('field_options_wrapper')->selectOption($field_type);
      }
    }
    // If the element could not be found then it is probably in a group.
    catch (ElementNotFoundException) {
      // Call the helper function to confirm it is in a group.
      $field_group = $this->getFieldFromGroup($field_type);
      $this->clickLink($field_group);
      $this->assertSession()->assertWaitOnAjaxRequest();
      $this->assertSession()->fieldExists('field_options_wrapper')->selectOption($field_type);
    }
    $field_label = $page->findField('label');
    $this->assertTrue($field_label->isVisible());
    $field_label = $page->find('css', 'input[data-drupal-selector="edit-label"]');
    $field_label->setValue($label);
    $machine_name = $assert_session->waitForElementVisible('css', '[data-drupal-selector="edit-label"] + * .machine-name-value');
    $this->assertNotEmpty($machine_name);
    $page->findButton('Edit')->press();

    $field_field_name = $page->findField('field_name');
    $this->assertTrue($field_field_name->isVisible());
    $field_field_name->setValue($field_name);

    $this->assertSession()->elementExists('xpath', '//button[text()="Continue"]')->press();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->waitForElementVisible('css', '#drupal-modal');

    $assert_session->waitForText("These settings apply to the $label field everywhere it is used.");
    if ($save_settings) {
      // Second step: Save field settings.
      $save_button = $page->find('css', '.ui-dialog-buttonpane')->findButton('Save');
      $save_button->click();
      $assert_session->assert($assert_session->waitForText("Saved $label configuration."), 'text not found');

      // Check that the field appears in the overview form.
      $row = $page->find('css', '#field-' . $field_name);
      $this->assertNotEmpty($row, 'Field was created and appears in the overview page.');
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
   * @param string|null $label
   *   (optional) The label of the new field. Defaults to a random string.
   * @param array $field_edit
   *   (optional) $edit parameter for submitForm() on the second step
   *   ('Field settings' form).
   */
  public function fieldUIAddExistingFieldJS(string $bundle_path, string $existing_storage_name, ?string $label = NULL, array $field_edit = []): void {
    $label = $label ?: $this->randomMachineName();
    $field_edit['edit-label'] = $label;

    // First step: navigate to the re-use field page.
    $this->drupalGet("{$bundle_path}/fields/");
    // Confirm that the local action is visible.
    $this->assertSession()->linkExists('Re-use an existing field');
    $this->clickLink('Re-use an existing field');
    // Wait for the modal to open.
    $this->assertSession()->waitForElementVisible('css', '#drupal-modal');
    $this->assertSession()->elementExists('css', "input[value=Re-use][name=$existing_storage_name]");
    $this->click("input[value=Re-use][name=$existing_storage_name]");

    // Set the main content to only the content region because the label can
    // contain HTML which will be auto-escaped by Twig.
    $this->assertSession()->responseContains('field-config-edit-form');
    // Check that the page does not have double escaped HTML tags.
    $this->assertSession()->responseNotContains('&amp;lt;');

    // Second step: 'Field settings' form.
    $this->submitForm($field_edit, 'Save settings');
    $this->assertSession()->assert($this->assertSession()->waitForText("Saved $label configuration."), 'text not found');

    // Check that the field appears in the overview form.
    $xpath = $this->assertSession()->buildXPathQuery("//table[@id=\"field-overview\"]//tr/td[1 and text() = :label]", [
      ':label' => $label,
    ]);
    $this->assertSession()->elementExists('xpath', $xpath);
  }

}
