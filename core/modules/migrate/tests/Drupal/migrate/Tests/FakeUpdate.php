<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\FakeUpdate.
 */

namespace Drupal\migrate\Tests;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\Update;

/**
 * Defines FakeUpdate for use in database tests.
 */
class FakeUpdate extends Update {

  /**
   * The database table to update.
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
   * Constructs a FakeUpdate object and initializes the condition.
   *
   * @param array $database_contents
   *   The database contents faked as an array. Each key is a table name, each
   *   value is a list of table rows.
   * @param string $table
   *   The table to update.
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
      $fields = $this->fields;
      $condition = $this->condition;
      array_walk($this->databaseContents[$this->table], function (&$row_array) use ($fields, $condition, &$affected) {
        $row = new DatabaseRow($row_array);
        if (ConditionResolver::matchGroup($row, $condition)) {
          $row_array = $fields + $row_array;
          $affected++;
        }
      });
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

  /**
   * {@inheritdoc}
   */
  public function expression($field, $expression, array $arguments = NULL) {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

}
