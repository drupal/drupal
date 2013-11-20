<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\DatabaseRow.
 */

namespace Drupal\migrate\Tests;

class DatabaseRow implements DatabaseRowInterface {

  public function __construct(array $row) {
    $this->row = $row;
  }

  public function getValue($field) {
    return $this->row[$field];
  }
}
