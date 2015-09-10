<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldDefaultValueCallbackProvider.
 */

namespace Drupal\field\Tests;

/**
 * Helper class for \Drupal\field\Tests\FieldDefaultValueCallbackTest.
 */
class FieldDefaultValueCallbackProvider {

  /**
   * Helper callback calculating a default value.
   */
  public static function calculateDefaultValue() {
    return [['value' => 'Calculated default value']];
  }

}
