<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\Traits;

/**
 * Provides common functionality for the Field UI tests that depend on JS.
 */
trait FieldUiJSTestTrait {

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
    $label = $label ?: $field_name;

    // Allow the caller to set a NULL path in case they navigated to the right
    // page before calling this method.
    if ($bundle_path !== NULL) {
      $bundle_path = "$bundle_path/fields/add-field";
      $this->drupalGet($bundle_path);
    }

    // First step: 'Add field' page.
    $session = $this->getSession();

    $page = $session->getPage();
    $assert_session = $this->assertSession();

    if ($assert_session->waitForElementVisible('css', "[name='new_storage_type'][value='$field_type']")) {
      $page = $this->getSession()->getPage();
      $field_card = $page->find('css', "[name='new_storage_type'][value='$field_type']")->getParent();
    }
    else {
      $field_card = $this->getFieldFromGroupJS($field_type);
    }
    $field_card?->click();
    $page->findButton('Continue')->click();
    $field_label = $page->findField('edit-label');
    $this->assertTrue($field_label->isVisible());
    $field_label = $page->find('css', 'input[data-drupal-selector="edit-label"]');
    $field_label->setValue($label);
    $machine_name = $assert_session->waitForElementVisible('css', '[data-drupal-selector="edit-label"] + * .machine-name-value');
    $this->assertNotEmpty($machine_name);
    $page->findButton('Edit')->press();

    $field_field_name = $page->findField('field_name');
    $this->assertTrue($field_field_name->isVisible());
    $field_field_name->setValue($field_name);

    $page->findButton('Continue')->click();
    $assert_session->waitForText("These settings apply to the $label field everywhere it is used.");
    if ($save_settings) {
      // Second step: Save field settings.
      $page->findButton('Save settings')->click();
      $assert_session->pageTextContains("Saved $label configuration.");

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
    $this->assertSession()->pageTextContains("Saved $label configuration.");

    // Check that the field appears in the overview form.
    $xpath = $this->assertSession()->buildXPathQuery("//table[@id=\"field-overview\"]//tr/td[1 and text() = :label]", [
      ':label' => $label,
    ]);
    $this->assertSession()->elementExists('xpath', $xpath);
  }

  /**
   * Helper function that returns the field card element if it is in a group.
   *
   * @param string $field_type
   *   The name of the field type.
   *
   * @return \Behat\Mink\Element\NodeElement|false|mixed|null
   *   Field card element within a group.
   */
  public function getFieldFromGroupJS($field_type) {
    $group_elements = $this->getSession()->getPage()->findAll('css', '.field-option-radio');
    $groups = [];
    foreach ($group_elements as $group_element) {
      $groups[] = $group_element->getAttribute('value');
    }
    $field_card = NULL;
    foreach ($groups as $group) {
      $group_field_card = $this->getSession()->getPage()->find('css', "[name='new_storage_type'][value='$group']")->getParent();
      $group_field_card->click();
      $this->getSession()->getPage()->pressButton('Continue');
      $field_card = $this->getSession()->getPage()->find('css', "[name='group_field_options_wrapper'][value='$field_type']");
      if ($field_card) {
        break;
      }
      $this->getSession()->getPage()->pressButton('Back');
    }
    return $field_card->getParent();
  }

}
