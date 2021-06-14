<?php

namespace Drupal\Core\Database\Driver\corefake;

use Drupal\Driver\Database\fake\Connection as BaseConnection;

class Connection extends BaseConnection {

  /**
   * {@inheritdoc}
   */
  public $driver = 'corefake';

}
