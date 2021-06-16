<?php

namespace Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses;

use Drupal\Driver\Database\fake\Connection as BaseConnection;

/**
 * CorefakeWithAllCustomClasses implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends BaseConnection {

  /**
   * {@inheritdoc}
   */
  public $driver = 'corefakeWithAllCustomClasses';

}
