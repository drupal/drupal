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
  public static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Tests placeholder text for elements that support placeholders.
   */
  public function testPlaceHolderText() {
    $this->drupalGet('form-test/placeholder-text');
    $expected = 'placeholder-text';
    // Test to make sure non-textarea elements have the proper placeholder text.
    foreach (['textfield', 'tel', 'url', 'password', 'email', 'number'] as $type) {
      $element = $this->xpath('//input[@id=:id and @placeholder=:expected]', [
        ':id' => 'edit-' . $type,
        ':expected' => $expected,
      ]);
      $this->assertTrue(!empty($element), new FormattableMarkup('Placeholder text placed in @type.', ['@type' => $type]));
    }

    // Test to make sure textarea has the proper placeholder text.
    $element = $this->xpath('//textarea[@id=:id and @placeholder=:expected]', [
      ':id' => 'edit-textarea',
      ':expected' => $expected,
    ]);
    $this->assertTrue(!empty($element), 'Placeholder text placed in textarea.');
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
        $this->assertIdentical((string) $element->getAttribute('value'), $expected);
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
        $this->assertIdentical((string) $element->getAttribute('value'), $expected);
      }
    }
    // Verify that custom #description properties are output.
    foreach (['checkboxes', 'radios'] as $type) {
      $elements = $this->xpath('//input[@id=:id]/following-sibling::div[@class=:class]', [
        ':id' => 'edit-' . $type . '-foo',
        ':class' => 'description',
      ]);
      $this->assertGreaterThan(0, count($elements), new FormattableMarkup('Custom %type option description found.', [
        '%type' => $type,
      ]));
    }
  }

  /**
   * Tests correct checked attribute for radios element.
   */
  public function testRadiosChecked() {
    // Verify that there is only one radio option checked.
    $this->drupalGet('form-test/radios-checked');
    $elements = $this->xpath('//input[@name="radios" and @checked]');
    $this->assertCount(1, $elements);
    $this->assertSame('0', $elements[0]->getValue());
    $elements = $this->xpath('//input[@name="radios-string" and @checked]');
    $this->assertCount(1, $elements);
    $this->assertSame('bar', $elements[0]->getValue());
    $elements = $this->xpath('//input[@name="radios-boolean-true" and @checked]');
    $this->assertCount(1, $elements);
    $this->assertSame('1', $elements[0]->getValue());
    // A default value of FALSE indicates that nothing is set.
    $elements = $this->xpath('//input[@name="radios-boolean-false" and @checked]');
    $this->assertCount(0, $elements);
    $elements = $this->xpath('//input[@name="radios-boolean-any" and @checked]');
    $this->assertCount(1, $elements);
    $this->assertSame('All', $elements[0]->getValue());
    $elements = $this->xpath('//input[@name="radios-string-zero" and @checked]');
    $this->assertCount(1, $elements);
    $this->assertSame('0', $elements[0]->getValue());
    $elements = $this->xpath('//input[@name="radios-int-non-zero" and @checked]');
    $this->assertCount(1, $elements);
    $this->assertSame('10', $elements[0]->getValue());
    $elements = $this->xpath('//input[@name="radios-int-non-zero-as-string" and @checked]');
    $this->assertCount(1, $elements);
    $this->assertSame('100', $elements[0]->getValue());
    $elements = $this->xpath('//input[@name="radios-empty-string" and @checked]');
    $this->assertCount(1, $elements);
    $this->assertSame('0', $elements[0]->getValue());
    $elements = $this->xpath('//input[@name="radios-empty-array" and @checked]');
    $this->assertCount(0, $elements);
    $elements = $this->xpath('//input[@name="radios-key-FALSE" and @checked]');
    $this->assertCount(1, $elements);
    $this->assertSame('0', $elements[0]->getValue());
  }

  /**
   * Tests wrapper ids for checkboxes and radios.
   */
  public function testWrapperIds() {
    $this->drupalGet('form-test/checkboxes-radios');

    // Verify that wrapper id is different from element id.
    foreach (['checkboxes', 'radios'] as $type) {
      $element_ids = $this->xpath('//div[@id=:id]', [':id' => 'edit-' . $type]);
      $wrapper_ids = $this->xpath('//fieldset[@id=:id]', [':id' => 'edit-' . $type . '--wrapper']);
      $this->assertTrue(count($element_ids) == 1, new FormattableMarkup('A single element id found for type %type', ['%type' => $type]));
      $this->assertTrue(count($wrapper_ids) == 1, new FormattableMarkup('A single wrapper id found for type %type', ['%type' => $type]));
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
    $this->assertEqual(2, count($this->xpath('//*[contains(concat(" ", @class, " "), " button ")]')));
    $this->assertEqual(1, count($this->xpath('//*[contains(concat(" ", @class, " "), " button--foo ")]')));
    $this->assertEqual(1, count($this->xpath('//*[contains(concat(" ", @class, " "), " button--danger ")]')));
  }

  /**
   * Tests the #group property.
   */
  public function testGroupElements() {
    $this->drupalGet('form-test/group-details');
    $elements = $this->xpath('//div[@class="details-wrapper"]//div[@class="details-wrapper"]//label');
    $this->assertTrue(count($elements) == 1);
    $this->drupalGet('form-test/group-container');
    $elements = $this->xpath('//div[@id="edit-container"]//div[@class="details-wrapper"]//label');
    $this->assertTrue(count($elements) == 1);
    $this->drupalGet('form-test/group-fieldset');
    $elements = $this->xpath('//fieldset[@id="edit-fieldset"]//div[@id="edit-meta"]//label');
    $this->assertTrue(count($elements) == 1);
    $this->drupalGet('form-test/group-vertical-tabs');
    $elements = $this->xpath('//div[@data-vertical-tabs-panes]//details[@id="edit-meta"]//label');
    $this->assertTrue(count($elements) == 1);
    $elements = $this->xpath('//div[@data-vertical-tabs-panes]//details[@id="edit-meta-2"]//label');
    $this->assertTrue(count($elements) == 1);
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
   * Tests a form with a autocomplete setting..
   */
  public function testFormAutocomplete() {
    $this->drupalGet('form-test/autocomplete');

    $result = $this->xpath('//input[@id="edit-autocomplete-1" and contains(@data-autocomplete-path, "form-test/autocomplete-1")]');
    $this->assertEqual(count($result), 0, 'Ensure that the user does not have access to the autocompletion');
    $result = $this->xpath('//input[@id="edit-autocomplete-2" and contains(@data-autocomplete-path, "form-test/autocomplete-2/value")]');
    $this->assertEqual(count($result), 0, 'Ensure that the user does not have access to the autocompletion');

    $user = $this->drupalCreateUser(['access autocomplete test']);
    $this->drupalLogin($user);
    $this->drupalGet('form-test/autocomplete');

    // Make sure that the autocomplete library is added.
    $this->assertRaw('core/misc/autocomplete.js');

    $result = $this->xpath('//input[@id="edit-autocomplete-1" and contains(@data-autocomplete-path, "form-test/autocomplete-1")]');
    $this->assertEqual(count($result), 1, 'Ensure that the user does have access to the autocompletion');
    $result = $this->xpath('//input[@id="edit-autocomplete-2" and contains(@data-autocomplete-path, "form-test/autocomplete-2/value")]');
    $this->assertEqual(count($result), 1, 'Ensure that the user does have access to the autocompletion');
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
