<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\FakeInsert.
 */

namespace Drupal\migrate\Tests;

use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Defines FakeInsert for use in database tests.
 */
class FakeInsert extends Insert {

  /**
   * The database contents.
   *
   * @var array
   */
  protected $databaseContents;

  /**
   * The database table to insert into.
   *
   * @var string
   */
  protected $table;

  /**
   * Constructs a fake insert object.
   *
   * @param array $database_contents
   *   The database contents faked as an array. Each key is a table name, each
   *   value is a list of table rows.
   * @param string $table
   *   The table to insert into.
   * @param array $options
   *   (optional) The database options. Not used.
   */
  public function __construct(array &$database_contents, $table, array $options = array()) {
    $this->databaseContents = &$database_contents;
    $this->table = $table;
  }

  /**
   * {@inheritdoc}
   */
  public function useDefaults(array $fields) {
    throw new \Exception('This method is not supported');
  }

  /**
   * {@inheritdoc}
   */
  public function from(SelectInterface $query) {
    throw new \Exception('This method is not supported');
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    foreach ($this->insertValues as $values) {
      $this->databaseContents[$this->table][] = array_combine($this->insertFields, $values);
    }
  }

}
