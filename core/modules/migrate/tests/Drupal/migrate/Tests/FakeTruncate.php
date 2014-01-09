<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\FakeTruncate.
 */

namespace Drupal\migrate\Tests;

/**
 * Defines FakeTruncate for use in database tests.
 */
class FakeTruncate {

  /**
   * Constructs a FakeTruncate object.
   *
   * @param array $database_contents
   *   The database contents faked as an array. Each key is a table name, each
   *   value is a list of table rows.
   * @param $table
   *   The table to truncate.
   */
  public function __construct(array &$database_contents, $table) {
    $this->databaseContents = &$database_contents;
    $this->table = $table;
  }

  /**
   * Executes the TRUNCATE query.
   */
  public function execute() {
    $this->databaseContents[$this->table] = array();
  }

}
