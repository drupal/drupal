<?php

namespace Drupal\pgsql\Driver\Database\pgsql;

use Drupal\Core\Database\Query\Merge as QueryMerge;

@trigger_error('Extending from \Drupal\pgsql\Driver\Database\pgsql\Merge is deprecated in drupal:11.0.0 and is removed from drupal:12.0.0. Extend from the base class instead. See https://www.drupal.org/node/3256524', E_USER_DEPRECATED);

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Query\Merge.
 */
class Merge extends QueryMerge {
}
