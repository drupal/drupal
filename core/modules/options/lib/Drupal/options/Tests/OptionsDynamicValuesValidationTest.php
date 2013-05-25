<?php

/**
 * @file
 * Definition of Drupal\options\Tests\OptionsDynamicValuesValidationTest.
 */

namespace Drupal\options\Tests;

use Drupal\Core\Language\Language;
use Drupal\field\FieldValidationException;

/**
 * Tests the Options field allowed values function.
 */
class OptionsDynamicValuesValidationTest extends OptionsDynamicValuesTest {
  public static function getInfo() {
    return array(
      'name' => 'Options field dynamic values',
      'description' => 'Test the Options field allowed values function.',
      'group' => 'Field types',
    );
  }

  /**
   * Test that allowed values function gets the entity.
   */
  function testDynamicAllowedValues() {
    // Verify that the test passes against every value we had.
    foreach ($this->test as $key => $value) {
      $this->entity->test_options[Language::LANGCODE_NOT_SPECIFIED][0]['value'] = $value;
      try {
        field_attach_validate($this->entity);
        $this->pass("$key should pass");
      }
      catch (FieldValidationException $e) {
        // This will display as an exception, no need for a separate error.
        throw($e);
      }
    }
    // Now verify that the test does not pass against anything else.
    foreach ($this->test as $key => $value) {
      $this->entity->test_options[Language::LANGCODE_NOT_SPECIFIED][0]['value'] = is_numeric($value) ? (100 - $value) : ('X' . $value);
      $pass = FALSE;
      try {
        field_attach_validate($this->entity);
      }
      catch (FieldValidationException $e) {
        $pass = TRUE;
      }
      $this->assertTrue($pass, $key . ' should not pass');
    }
  }
}
