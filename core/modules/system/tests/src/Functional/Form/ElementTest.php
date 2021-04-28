<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests building and processing of core form elements.
 *
 * @group Form
 */
class ElementTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Tests placeholder text for elements that support placeholders.
   */
  public function testPlaceHolderText() {
    $this->drupalGet('form-test/placeholder-text');
    foreach (['textfield', 'tel', 'url', 'password', 'email', 'number', 'textarea'] as $type) {
      $field = $this->assertSession()->fieldExists("edit-$type");
      $this->assertSame('placeholder-text', $field->getAttribute('placeholder'));
    }
  }

  /**
   * Tests expansion of #options for #type checkboxes and radios.
   */
  public function testOptions() {
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
    $this->assertRaw("<em>Special Char</em>alert('checkboxes');");
    $this->assertRaw("<em>Special Char</em>alert('radios');");
    $this->assertRaw('<em>Bar - checkboxes</em>');
    $this->assertRaw('<em>Bar - radios</em>');

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
  public function testRadiosChecked() {
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
  public function testWrapperIds() {
    $this->drupalGet('form-test/checkboxes-radios');

    // Verify that wrapper id is different from element id.
    foreach (['checkboxes', 'radios'] as $type) {
      // A single element id is found.
      $this->assertSession()->elementsCount('xpath', "//div[@id='edit-$type']", 1);
      $wrapper_ids = $this->xpath('//fieldset[@id=:id]', [':id' => 'edit-' . $type . '--wrapper']);
      $this->assertCount(1, $wrapper_ids, new FormattableMarkup('A single wrapper id found for type %type', ['%type' => $type]));
    }
  }

  /**
   * Tests button classes.
   */
  public function testButtonClasses() {
    $this->drupalGet('form-test/button-class');
    // Just contains(@class, "button") won't do because then
    // "button--foo" would contain "button". Instead, check
    // " button ". Make sure it matches in the beginning and the end too
    // by adding a space before and after.
    $this->assertCount(2, $this->xpath('//*[contains(concat(" ", @class, " "), " button ")]'));
    $this->assertCount(1, $this->xpath('//*[contains(concat(" ", @class, " "), " button--foo ")]'));
    $this->assertCount(1, $this->xpath('//*[contains(concat(" ", @class, " "), " button--danger ")]'));
  }

  /**
   * Tests the #group property.
   */
  public function testGroupElements() {
    $this->drupalGet('form-test/group-details');
    $this->assertSession()->elementsCount('xpath', '//div[@class="details-wrapper"]//div[@class="details-wrapper"]//label', 1);
    $this->drupalGet('form-test/group-container');
    $this->assertSession()->elementsCount('xpath', '//div[@id="edit-container"]//div[@class="details-wrapper"]//label', 1);
    $this->drupalGet('form-test/group-fieldset');
    $this->assertSession()->elementsCount('xpath', '//fieldset[@id="edit-fieldset"]//div[@id="edit-meta"]//label', 1);
    $this->drupalGet('form-test/group-vertical-tabs');
    $this->assertSession()->elementsCount('xpath', '//div[@data-vertical-tabs-panes]//details[@id="edit-meta"]//label', 1);
    $this->assertSession()->elementsCount('xpath', '//div[@data-vertical-tabs-panes]//details[@id="edit-meta-2"]//label', 1);
  }

  /**
   * Tests the #required property on details and fieldset elements.
   */
  public function testRequiredFieldsetsAndDetails() {
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
  public function testFormAutocomplete() {
    $this->drupalGet('form-test/autocomplete');

    // Ensure that the user does not have access to the autocompletion.
    $this->assertSession()->elementNotExists('xpath', '//input[@id="edit-autocomplete-1" and contains(@data-autocomplete-path, "form-test/autocomplete-1")]');
    $this->assertSession()->elementNotExists('xpath', '//input[@id="edit-autocomplete-2" and contains(@data-autocomplete-path, "form-test/autocomplete-2/value")]');

    $user = $this->drupalCreateUser(['access autocomplete test']);
    $this->drupalLogin($user);
    $this->drupalGet('form-test/autocomplete');

    // Make sure that the autocomplete library is added.
    $this->assertRaw('core/misc/autocomplete.js');

    // Ensure that the user does have access to the autocompletion.
    $this->assertSession()->elementExists('xpath', '//input[@id="edit-autocomplete-1" and contains(@data-autocomplete-path, "form-test/autocomplete-1")]');
    $this->assertSession()->elementExists('xpath', '//input[@id="edit-autocomplete-2" and contains(@data-autocomplete-path, "form-test/autocomplete-2/value")]');
  }

  /**
   * Tests form element error messages.
   */
  public function testFormElementErrors() {
    $this->drupalPostForm('form_test/details-form', [], 'Submit');
    $this->assertText('I am an error on the details element.');
  }

  /**
   * Tests summary attributes of details.
   */
  public function testDetailsSummaryAttributes() {
    $this->drupalGet('form-test/group-details');
    $this->assertSession()->elementExists('css', 'summary[data-summary-attribute="test"]');
  }

}
