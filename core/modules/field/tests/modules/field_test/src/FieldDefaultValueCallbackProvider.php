<?php

namespace Drupal\field_test;

/**
 * Helper class for \Drupal\Tests\field\Functional\FieldDefaultValueCallbackTest.
 */
class FieldDefaultValueCallbackProvider {

  /**
   * Helper callback calculating a default value.
   */
  public static function calculateDefaultValue() {
    return [['value' => 'Calculated default value']];
  }

}
