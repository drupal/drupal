<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\DatabaseRow.
 */

namespace Drupal\migrate\Tests;

class DatabaseRow implements DatabaseRowInterface {

  /**
   * Construct a new row.
   *
   * @param array $row
   *   The row data.
   */
  public function __construct(array $row) {
    $this->row = $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($field) {
    return $this->row[$field];
  }

}
