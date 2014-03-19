<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\DatabaseRowInterface.
 */

namespace Drupal\migrate\Tests;

interface DatabaseRowInterface {

  /**
   * Get the field value from the row.
   *
   * @param mixed $field
   *   The field to get the value of.
   *
   * @return mixed
   *   The field value from the row.
   */
  public function getValue($field);
}
