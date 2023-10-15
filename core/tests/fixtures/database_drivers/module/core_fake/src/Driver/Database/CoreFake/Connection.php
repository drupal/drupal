<?php

namespace Drupal\core_fake\Driver\Database\CoreFake;

use Drupal\Driver\Database\fake\Connection as BaseConnection;

/**
 * CoreFake implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends BaseConnection {

  /**
   * {@inheritdoc}
   */
  public $driver = 'CoreFake';

}
