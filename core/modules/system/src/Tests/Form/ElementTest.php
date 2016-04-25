<?php

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Tests building and processing of core form elements.
 *
 * @group Form
 */
class ElementTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  /**
   * Tests placeholder text for elements that support placeholders.
   */
  function testPlaceHolderText() {
    $this->drupalGet('form-test/placeholder-text');
    $expected = 'placeholder-text';
    // Test to make sure non-textarea elements have the proper placeholder text.
    foreach (array('textfield', 'tel', 'url', 'password', 'email', 'number') as $type) {
      $element = $this->xpath('//input[@id=:id and @placeholder=:expected]', array(
        ':id' => 'edit-' . $type,
        ':expected' => $expected,
      ));
      $this->assertTrue(!empty($element), format_string('Placeholder text placed in @type.', array('@type' => $type)));
    }

    // Test to make sure textarea has the proper placeholder text.
    $element = $this->xpath('//textarea[@id=:id and @placeholder=:expected]', array(
      ':id' => 'edit-textarea',
      ':expected' => $expected,
    ));
    $this->assertTrue(!empty($element), 'Placeholder text placed in textarea.');
  }

  /**
   * Tests expansion of #options for #type checkboxes and radios.
   */
  function testOptions() {
    $this->drupalGet('form-test/checkboxes-radios');

    // Verify that all options appear in their defined order.
    foreach (array('checkbox', 'radio') as $type) {
      $elements = $this->xpath('//input[@type=:type]', array(':type' => $type));
      $expected_values = array('0', 'foo', '1', 'bar', '>');
      foreach ($elements as $element) {
        $expected = array_shift($expected_values);
        $this->assertIdentical((string) $element['value'], $expected);
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
    foreach (array('checkbox', 'radio') as $type) {
      $elements = $this->xpath('//input[@type=:type]', array(':type' => $type));
      $expected_values = array('0', 'foo', 'bar', '>', '1');
      foreach ($elements as $element) {
        $expected = array_shift($expected_values);
        $this->assertIdentical((string) $element['value'], $expected);
      }
    }
    // Verify that custom #description properties are output.
    foreach (array('checkboxes', 'radios') as $type) {
      $elements = $this->xpath('//input[@id=:id]/following-sibling::div[@class=:class]', array(
        ':id' => 'edit-' . $type . '-foo',
        ':class' => 'description',
      ));
      $this->assertTrue(count($elements), format_string('Custom %type option description found.', array(
        '%type' => $type,
      )));
    }
  }

  /**
   * Tests wrapper ids for checkboxes and radios.
   */
  function testWrapperIds() {
    $this->drupalGet('form-test/checkboxes-radios');

    // Verify that wrapper id is different from element id.
    foreach (array('checkboxes', 'radios') as $type) {
      $element_ids = $this->xpath('//div[@id=:id]', array(':id' => 'edit-' . $type));
      $wrapper_ids = $this->xpath('//fieldset[@id=:id]', array(':id' => 'edit-' . $type . '--wrapper'));
      $this->assertTrue(count($element_ids) == 1, format_string('A single element id found for type %type', array('%type' => $type)));
      $this->assertTrue(count($wrapper_ids) == 1, format_string('A single wrapper id found for type %type', array('%type' => $type)));
    }
  }

  /**
   * Tests button classes.
   */
  function testButtonClasses() {
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
  function testGroupElements() {
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
   * Tests a form with a autocomplete setting..
   */
  public function testFormAutocomplete() {
    $this->drupalGet('form-test/autocomplete');

    $result = $this->xpath('//input[@id="edit-autocomplete-1" and contains(@data-autocomplete-path, "form-test/autocomplete-1")]');
    $this->assertEqual(count($result), 0, 'Ensure that the user does not have access to the autocompletion');
    $result = $this->xpath('//input[@id="edit-autocomplete-2" and contains(@data-autocomplete-path, "form-test/autocomplete-2/value")]');
    $this->assertEqual(count($result), 0, 'Ensure that the user does not have access to the autocompletion');

    $user = $this->drupalCreateUser(array('access autocomplete test'));
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

}
