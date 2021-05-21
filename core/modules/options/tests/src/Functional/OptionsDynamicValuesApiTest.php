<?php

namespace Drupal\Tests\options\Functional;

/**
 * Tests the options allowed values api.
 *
 * @group options
 */
class OptionsDynamicValuesApiTest extends OptionsDynamicValuesTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests options_allowed_values().
   *
   * @see options_test_dynamic_values_callback()
   */
  public function testOptionsAllowedValues() {
    // Test allowed values without passed $items.
    $values = options_allowed_values($this->fieldStorage);
    $this->assertEquals([], $values);

    $values = options_allowed_values($this->fieldStorage, $this->entity);

    $expected_values = [
      $this->entity->label(),
      $this->entity->toUrl()->toString(),
      $this->entity->uuid(),
      $this->entity->bundle(),
    ];
    $expected_values = array_combine($expected_values, $expected_values);
    $this->assertEquals($expected_values, $values);
  }

}
