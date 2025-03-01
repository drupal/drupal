<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests building and processing of core form elements.
 *
 * @group Form
 */
class ElementTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * Test form elements.
   */
  public function testFormElements(): void {
    $this->testPlaceHolderText();
    $this->testOptions();
    $this->testRadiosChecked();
    $this->testWrapperIds();
    $this->testButtonClasses();
    $this->testSubmitButtonAttribute();
    $this->testGroupElements();
    $this->testRequiredFieldsetsAndDetails();
    $this->testFormAutocomplete();
    $this->testFormElementErrors();
    $this->testDetailsSummaryAttributes();
  }

  /**
   * Tests placeholder text for elements that support placeholders.
   */
  protected function testPlaceHolderText(): void {
    $this->drupalGet('form-test/placeholder-text');
    foreach (['textfield', 'tel', 'url', 'password', 'email', 'number', 'textarea'] as $type) {
      $field = $this->assertSession()->fieldExists("edit-$type");
      $this->assertSame('placeholder-text', $field->getAttribute('placeholder'));
    }
  }

  /**
   * Tests expansion of #options for #type checkboxes and radios.
   */
  protected function testOptions(): void {
    $this->drupalGet('form-test/checkboxes-radios');

    // Verify that all options appear in their defined order.
    foreach (['checkbox', 'radio'] as $type) {
      $elements = $this->xpath('//input[@type=:type]', [':type' => $type]);
      $expected_values = ['0', 'foo', '1', 'bar', '>'];
      foreach ($elements as $element) {
        $expected = array_shift($expected_values);
        $this->assertSame($expected, (string) $element->getAttribute('value'));
      }
    }

    // Verify that the choices are admin filtered as expected.
    $this->assertSession()->responseContains("<em>Special Char</em>alert('checkboxes');");
    $this->assertSession()->responseContains("<em>Special Char</em>alert('radios');");
    $this->assertSession()->responseContains('<em>Bar - checkboxes</em>');
    $this->assertSession()->responseContains('<em>Bar - radios</em>');

    // Enable customized option sub-elements.
    $this->drupalGet('form-test/checkboxes-radios/customize');

    // Verify that all options appear in their defined order, taking a custom
    // #weight into account.
    foreach (['checkbox', 'radio'] as $type) {
      $elements = $this->xpath('//input[@type=:type]', [':type' => $type]);
      $expected_values = ['0', 'foo', 'bar', '>', '1'];
      foreach ($elements as $element) {
        $expected = array_shift($expected_values);
        $this->assertSame($expected, (string) $element->getAttribute('value'));
      }
    }
    // Verify that custom #description properties are output.
    foreach (['checkboxes', 'radios'] as $type) {
      $this->assertSession()->elementExists('xpath', "//input[@id='edit-$type-foo']/following-sibling::div[@class='description']");
    }
  }

  /**
   * Tests correct checked attribute for radios element.
   */
  protected function testRadiosChecked(): void {
    // Verify that there is only one radio option checked.
    $this->drupalGet('form-test/radios-checked');
    $this->assertSession()->elementsCount('xpath', '//input[@name="radios" and @checked]', 1);
    $this->assertSession()->fieldValueEquals("radios", '0');
    $this->assertSession()->elementsCount('xpath', '//input[@name="radios-string" and @checked]', 1);
    $this->assertSession()->fieldValueEquals("radios-string", 'bar');
    $this->assertSession()->elementsCount('xpath', '//input[@name="radios-boolean-true" and @checked]', 1);
    $this->assertSession()->fieldValueEquals("radios-boolean-true", '1');
    // A default value of FALSE indicates that nothing is set.
    $this->assertSession()->elementNotExists('xpath', '//input[@name="radios-boolean-false" and @checked]');
    $this->assertSession()->elementsCount('xpath', '//input[@name="radios-boolean-any" and @checked]', 1);
    $this->assertSession()->fieldValueEquals("radios-boolean-any", 'All');
    $this->assertSession()->elementsCount('xpath', '//input[@name="radios-string-zero" and @checked]', 1);
    $this->assertSession()->fieldValueEquals("radios-string-zero", '0');
    $this->assertSession()->elementsCount('xpath', '//input[@name="radios-int-non-zero" and @checked]', 1);
    $this->assertSession()->fieldValueEquals("radios-int-non-zero", '10');
    $this->assertSession()->elementsCount('xpath', '//input[@name="radios-int-non-zero-as-string" and @checked]', 1);
    $this->assertSession()->fieldValueEquals("radios-int-non-zero-as-string", '100');
    $this->assertSession()->elementsCount('xpath', '//input[@name="radios-empty-string" and @checked]', 1);
    $this->assertSession()->fieldValueEquals("radios-empty-string", '0');
    $this->assertSession()->elementNotExists('xpath', '//input[@name="radios-empty-array" and @checked]');
    $this->assertSession()->elementsCount('xpath', '//input[@name="radios-key-FALSE" and @checked]', 1);
    $this->assertSession()->fieldValueEquals("radios-key-FALSE", '0');
  }

  /**
   * Tests wrapper ids for checkboxes and radios.
   */
  protected function testWrapperIds(): void {
    $this->drupalGet('form-test/checkboxes-radios');

    // Verify that wrapper id is different from element id.
    foreach (['checkboxes', 'radios'] as $type) {
      // A single element id is found.
      $this->assertSession()->elementsCount('xpath', "//div[@id='edit-{$type}']", 1);
      $this->assertSession()->elementsCount('xpath', "//fieldset[@id='edit-{$type}--wrapper']", 1);
    }
  }

  /**
   * Tests button classes.
   */
  protected function testButtonClasses(): void {
    $this->drupalGet('form-test/button-class');
    // Just contains(@class, "button") won't do because then
    // "button--foo" would contain "button". Instead, check
    // " button ". Make sure it matches in the beginning and the end too
    // by adding a space before and after.
    $this->assertSession()->elementsCount('xpath', '//*[contains(concat(" ", @class, " "), " button ")]', 2);
    $this->assertSession()->elementsCount('xpath', '//*[contains(concat(" ", @class, " "), " button--foo ")]', 1);
    $this->assertSession()->elementsCount('xpath', '//*[contains(concat(" ", @class, " "), " button--danger ")]', 1);
  }

  /**
   * Tests the submit_button attribute.
   */
  protected function testSubmitButtonAttribute(): void {
    // Set the submit_button attribute to true
    $this->drupalGet('form-test/submit-button-attribute');
    $this->assertSession()->elementsCount('xpath', '//input[@type="submit"]', 1);
    // Set the submit_button attribute to false
    $this->drupalGet('form-test/submit-button-attribute/1');
    $this->assertSession()->elementsCount('xpath', '//input[@type="button"]', 1);
  }

  /**
   * Tests the #group property.
   */
  protected function testGroupElements(): void {
    $this->drupalGet('form-test/group-details');
    $this->assertSession()->elementsCount('xpath', '//div[@class="details-wrapper"]//div[@class="details-wrapper"]//label', 1);
    $this->drupalGet('form-test/group-container');
    $this->assertSession()->elementsCount('xpath', '//div[@id="edit-container"]//div[@class="details-wrapper"]//label', 1);
    $this->drupalGet('form-test/group-fieldset');
    $this->assertSession()->elementsCount('xpath', '//fieldset[@id="edit-fieldset"]//div[@id="edit-meta"]//label', 1);
    $this->assertSession()->elementTextEquals('xpath', '//fieldset[@id="edit-fieldset-zero"]//legend', '0');
    $this->drupalGet('form-test/group-vertical-tabs');
    $this->assertSession()->elementsCount('xpath', '//div[@data-vertical-tabs-panes]//details[@id="edit-meta"]//label', 1);
    $this->assertSession()->elementsCount('xpath', '//div[@data-vertical-tabs-panes]//details[@id="edit-meta-2"]//label', 1);
  }

  /**
   * Tests the #required property on details and fieldset elements.
   */
  protected function testRequiredFieldsetsAndDetails(): void {
    $this->drupalGet('form-test/group-details');
    $this->assertEmpty($this->cssSelect('summary.form-required'));
    $this->drupalGet('form-test/group-details/1');
    $this->assertNotEmpty($this->cssSelect('summary.form-required'));
    $this->drupalGet('form-test/group-fieldset');
    $this->assertEmpty($this->cssSelect('span.form-required'));
    $this->drupalGet('form-test/group-fieldset/1');
    $this->assertNotEmpty($this->cssSelect('span.form-required'));
  }

  /**
   * Tests a form with an autocomplete setting..
   */
  protected function testFormAutocomplete(): void {
    $this->drupalGet('form-test/autocomplete');

    // Ensure that the user does not have access to the autocompletion.
    $this->assertSession()->elementNotExists('xpath', '//input[@id="edit-autocomplete-1" and contains(@data-autocomplete-path, "form-test/autocomplete-1")]');
    $this->assertSession()->elementNotExists('xpath', '//input[@id="edit-autocomplete-2" and contains(@data-autocomplete-path, "form-test/autocomplete-2/value")]');

    $user = $this->drupalCreateUser(['access autocomplete test']);
    $this->drupalLogin($user);
    $this->drupalGet('form-test/autocomplete');

    // Make sure that the autocomplete library is added.
    $this->assertSession()->responseContains('core/misc/autocomplete.js');

    // Ensure that the user does have access to the autocompletion.
    $this->assertSession()->elementExists('xpath', '//input[@id="edit-autocomplete-1" and contains(@data-autocomplete-path, "form-test/autocomplete-1")]');
    $this->assertSession()->elementExists('xpath', '//input[@id="edit-autocomplete-2" and contains(@data-autocomplete-path, "form-test/autocomplete-2/value")]');
  }

  /**
   * Tests form element error messages.
   */
  protected function testFormElementErrors(): void {
    $this->drupalGet('form_test/details-form');
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains('I am an error on the details element.');
  }

  /**
   * Tests summary attributes of details.
   */
  protected function testDetailsSummaryAttributes(): void {
    $this->drupalGet('form-test/group-details');
    $this->assertSession()->elementExists('css', 'summary[data-summary-attribute="test"]');
  }

}
