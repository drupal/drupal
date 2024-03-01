<?php

namespace Drupal\mysql\Driver\Database\mysql;

use Drupal\Core\Database\Query\Delete as QueryDelete;

@trigger_error('Extending from \Drupal\mysql\Driver\Database\mysql\Delete is deprecated in drupal:11.0.0 and is removed from drupal:12.0.0. Extend from the base class instead. See https://www.drupal.org/node/3256524', E_USER_DEPRECATED);

/**
 * MySQL implementation of \Drupal\Core\Database\Query\Delete.
 */
class Delete extends QueryDelete {
}
