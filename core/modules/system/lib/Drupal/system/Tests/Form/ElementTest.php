<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Form\ElementTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Tests building and processing of core form elements.
 */
class ElementTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Element processing',
      'description' => 'Tests building and processing of core form elements.',
      'group' => 'Form API',
    );
  }

  function setUp() {
    parent::setUp(array('form_test'));
  }

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
      $this->assertTrue(!empty($element), t('Placeholder text placed in @type.', array('@type' => $type)));
    }

    // Test to make sure textarea has the proper placeholder text.
    $element = $this->xpath('//textarea[@id=:id and @placeholder=:expected]', array(
      ':id' => 'edit-textarea',
      ':expected' => $expected,
    ));
    $this->assertTrue(!empty($element), t('Placeholder text placed in textarea.'));
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
      $this->assertTrue(count($elements), t('Custom %type option description found.', array(
        '%type' => $type,
      )));
    }
  }
}
