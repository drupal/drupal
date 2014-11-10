<?php

/**
 * @file
 * Contains Drupal\Core\Database\Driver\fake\FakeDelete.
 */

namespace Drupal\Core\Database\Driver\fake;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\Query\Delete;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Defines FakeDelete for use in database tests.
 */
class FakeDelete extends Delete {

  /**
   * The database table to delete from.
   *
   * @var string
   */
  protected $table;

  /**
   * The database contents.
   *
   * @var array
   */
  protected $databaseContents;

  /**
   * Constructs a FakeDelete object.
   *
   * @param array $database_contents
   *   The database contents faked as an array. Each key is a table name, each
   *   value is a list of table rows.
   * @param string $table
   *   The table to delete from.
   */
  public function __construct(array &$database_contents, $table) {
    $this->databaseContents = &$database_contents;
    $this->table = $table;
    $this->condition = new Condition('AND');
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $affected = 0;
    if (isset($this->databaseContents[$this->table])) {
      $original_row_count = count($this->databaseContents[$this->table]);
      $condition = $this->condition;
      $this->databaseContents[$this->table] = array_filter($this->databaseContents[$this->table], function ($row_array) use ($condition) {
        $row = new DatabaseRow($row_array);
        return !ConditionResolver::matchGroup($row, $condition);
      });
      $affected = $original_row_count - count($this->databaseContents[$this->table]);
    }
    return $affected;
  }

  /**
   * {@inheritdoc}
   */
  public function exists(SelectInterface $select) {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function where($snippet, $args = array()) {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

}
