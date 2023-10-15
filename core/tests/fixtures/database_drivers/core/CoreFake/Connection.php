<?php

namespace Drupal\Core\Database\Driver\CoreFake;

use Drupal\Driver\Database\fake\Connection as BaseConnection;

class Connection extends BaseConnection {

  /**
   * {@inheritdoc}
   */
  public $driver = 'CoreFake';

}
