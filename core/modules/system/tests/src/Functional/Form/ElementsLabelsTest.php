<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\form_test\Form\FormTestLabelForm;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests form element labels, required markers and associated output.
 *
 * @group Form
 */
class ElementsLabelsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test form elements, labels, title attributes and required marks output
   * correctly and have the correct label option class if needed.
   */
  public function testFormLabels() {
    $this->drupalGet('form_test/form-labels');

    // Check that the checkbox/radio processing is not interfering with
    // basic placement.
    $this->assertSession()->elementExists('xpath', '//input[@id="edit-form-checkboxes-test-third-checkbox"]/following-sibling::label[@for="edit-form-checkboxes-test-third-checkbox" and @class="option"]');

    // Make sure the label is rendered for checkboxes.
    $this->assertSession()->elementExists('xpath', '//input[@id="edit-form-checkboxes-test-0"]/following-sibling::label[@for="edit-form-checkboxes-test-0" and @class="option"]');
    $this->assertSession()->elementExists('xpath', '//input[@id="edit-form-radios-test-second-radio"]/following-sibling::label[@for="edit-form-radios-test-second-radio" and @class="option"]');

    // Make sure the label is rendered for radios.
    $this->assertSession()->elementExists('xpath', '//input[@id="edit-form-radios-test-0"]/following-sibling::label[@for="edit-form-radios-test-0" and @class="option"]');

    // Exercise various defaults for checkboxes and modifications to ensure
    // appropriate override and correct behavior.
    $this->assertSession()->elementExists('xpath', '//input[@id="edit-form-checkbox-test"]/following-sibling::label[@for="edit-form-checkbox-test" and @class="option"]');

    // Exercise various defaults for textboxes and modifications to ensure
    // appropriate override and correct behavior.
    $elements = $this->xpath('//label[@for="edit-form-textfield-test-title-and-required" and @class="js-form-required form-required"]/following-sibling::input[@id="edit-form-textfield-test-title-and-required"]');
    $this->assertTrue(isset($elements[0]), 'Label precedes textfield, with required marker inside label.');

    $this->assertSession()->elementExists('xpath', '//input[@id="edit-form-textfield-test-no-title-required"]/preceding-sibling::label[@for="edit-form-textfield-test-no-title-required" and @class="js-form-required form-required"]');
    $this->assertSession()->elementExists('xpath', '//input[@id="edit-form-textfield-test-title-invisible"]/preceding-sibling::label[@for="edit-form-textfield-test-title-invisible" and @class="visually-hidden"]');

    $this->assertSession()->elementNotExists('xpath', '//input[@id="edit-form-textfield-test-title"]/preceding-sibling::span[@class="js-form-required form-required"]');

    $this->assertSession()->elementExists('xpath', '//input[@id="edit-form-textfield-test-title-after"]/following-sibling::label[@for="edit-form-textfield-test-title-after" and @class="option"]');

    $elements = $this->xpath('//label[@for="edit-form-textfield-test-title-no-show"]');
    $this->assertFalse(isset($elements[0]), 'No label tag when title set not to display.');

    $elements = $this->xpath('//div[contains(@class, "js-form-item-form-textfield-test-title-invisible") and contains(@class, "form-no-label")]');
    $this->assertTrue(isset($elements[0]), 'Field class is form-no-label when there is no label.');

    // Check #field_prefix and #field_suffix placement.
    $elements = $this->xpath('//span[@class="field-prefix"]/following-sibling::div[@id="edit-form-radios-test"]');
    $this->assertTrue(isset($elements[0]), 'Properly placed the #field_prefix element after the label and before the field.');

    $elements = $this->xpath('//span[@class="field-suffix"]/preceding-sibling::div[@id="edit-form-radios-test"]');
    $this->assertTrue(isset($elements[0]), 'Properly places the #field_suffix element immediately after the form field.');

    // Check #prefix and #suffix placement.
    $elements = $this->xpath('//div[@id="form-test-textfield-title-prefix"]/following-sibling::div[contains(@class, \'js-form-item-form-textfield-test-title\')]');
    $this->assertTrue(isset($elements[0]), 'Properly places the #prefix element before the form item.');

    $elements = $this->xpath('//div[@id="form-test-textfield-title-suffix"]/preceding-sibling::div[contains(@class, \'js-form-item-form-textfield-test-title\')]');
    $this->assertTrue(isset($elements[0]), 'Properly places the #suffix element before the form item.');

    // Check title attribute for radios and checkboxes.
    $this->assertSession()->elementAttributeContains('css', '#edit-form-checkboxes-title-attribute', 'title', 'Checkboxes test (Required)');
    $this->assertSession()->elementAttributeContains('css', '#edit-form-radios-title-attribute', 'title', 'Radios test (Required)');

    $elements = $this->xpath('//fieldset[@id="edit-form-checkboxes-title-invisible--wrapper"]/legend/span[contains(@class, "visually-hidden")]');
    $this->assertTrue(!empty($elements), "Title/Label not displayed when 'visually-hidden' attribute is set in checkboxes.");

    $elements = $this->xpath('//fieldset[@id="edit-form-radios-title-invisible--wrapper"]/legend/span[contains(@class, "visually-hidden")]');
    $this->assertTrue(!empty($elements), "Title/Label not displayed when 'visually-hidden' attribute is set in radios.");
  }

  /**
   * Tests XSS-protection of element labels.
   */
  public function testTitleEscaping() {
    $this->drupalGet('form_test/form-labels');
    foreach (FormTestLabelForm::$typesWithTitle as $type) {
      $this->assertSession()->responseContains("$type alert('XSS') is XSS filtered!");
      $this->assertSession()->responseNotContains("$type <script>alert('XSS')</script> is XSS filtered!");
    }
  }

  /**
   * Tests different display options for form element descriptions.
   */
  public function testFormDescriptions() {
    $this->drupalGet('form_test/form-descriptions');

    // Check #description placement with #description_display='after'.
    $field_id = 'edit-form-textfield-test-description-after';
    $description_id = $field_id . '--description';
    $this->assertSession()->elementExists('xpath', '//input[@id="' . $field_id . '" and @aria-describedby="' . $description_id . '"]/following-sibling::div[@id="' . $description_id . '"]');

    // Check #description placement with #description_display='before'.
    $field_id = 'edit-form-textfield-test-description-before';
    $description_id = $field_id . '--description';
    $this->assertSession()->elementExists('xpath', '//input[@id="' . $field_id . '" and @aria-describedby="' . $description_id . '"]/preceding-sibling::div[@id="' . $description_id . '"]');

    // Check if the class is 'visually-hidden' on the form element description
    // for the option with #description_display='invisible' and also check that
    // the description is placed after the form element.
    $field_id = 'edit-form-textfield-test-description-invisible';
    $description_id = $field_id . '--description';
    $this->assertSession()->elementExists('xpath', '//input[@id="' . $field_id . '" and @aria-describedby="' . $description_id . '"]/following-sibling::div[contains(@class, "visually-hidden")]');
  }

  /**
   * Test forms in theme-less environments.
   */
  public function testFormsInThemeLessEnvironments() {
    $form = $this->getFormWithLimitedProperties();
    $render_service = $this->container->get('renderer');
    // This should not throw any notices.
    $render_service->renderPlain($form);
  }

  /**
   * Return a form with element with not all properties defined.
   */
  protected function getFormWithLimitedProperties() {
    $form = [];

    $form['fieldset'] = [
      '#type' => 'fieldset',
      '#title' => 'Fieldset',
    ];

    return $form;
  }

}
