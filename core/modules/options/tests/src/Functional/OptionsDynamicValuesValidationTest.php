<?php

namespace Drupal\Tests\options\Functional;

/**
 * Tests the Options field allowed values function.
 *
 * @group options
 */
class OptionsDynamicValuesValidationTest extends OptionsDynamicValuesTestBase {
  /**
   * Test that allowed values function gets the entity.
   */
  public function testDynamicAllowedValues() {
    // Verify that validation passes against every value we had.
    foreach ($this->test as $key => $value) {
      $this->entity->test_options->value = $value;
      $violations = $this->entity->test_options->validate();
      $this->assertEqual(count($violations), 0, "$key is a valid value");
    }

    // Now verify that validation does not pass against anything else.
    foreach ($this->test as $key => $value) {
      $this->entity->test_options->value = is_numeric($value) ? (100 - $value) : ('X' . $value);
      $violations = $this->entity->test_options->validate();
      $this->assertEqual(count($violations), 1, "$key is not a valid value");
    }
  }

}
