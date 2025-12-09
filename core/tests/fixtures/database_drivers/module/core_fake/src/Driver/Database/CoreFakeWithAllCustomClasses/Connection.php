<?php

namespace Drupal\core_fake\Driver\Database\CoreFakeWithAllCustomClasses;

use Drupal\Driver\Database\fake\Connection as BaseConnection;

/**
 * CoreFakeWithAllCustomClasses implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends BaseConnection {

  /**
   * {@inheritdoc}
   */
  public $driver = 'CoreFakeWithAllCustomClasses';

  /**
   * {@inheritdoc}
   */
  public function exceptionHandler(): ExceptionHandler {
    return new ExceptionHandler();
  }

  /**
   * {@inheritdoc}
   */
  public function select($table, $alias = NULL, array $options = []): Select {
    return new Select($this, $table, $alias, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function insert($table, array $options = []): Insert {
    return new Insert($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function merge($table, array $options = []): Merge {
    return new Merge($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function upsert($table, array $options = []): Upsert {
    return new Upsert($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function update($table, array $options = []): Update {
    return new Update($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($table, array $options = []): Delete {
    return new Delete($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function truncate($table, array $options = []): Truncate {
    return new Truncate($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function schema() {
    if (empty($this->schema)) {
      $this->schema = new Schema($this);
    }
    return $this->schema;
  }

  /**
   * {@inheritdoc}
   */
  public function condition($conjunction): Condition {
    return new Condition($conjunction, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function startTransaction($name = ''): Transaction {
    return new Transaction($this, $name);
  }

}
