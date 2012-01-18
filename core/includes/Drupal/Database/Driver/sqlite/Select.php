<?php

namespace Drupal\Database\Driver\sqlite;

use Drupal\Database\Query\Select as QuerySelect;

class Select extends QuerySelect {
  public function forUpdate($set = TRUE) {
    // SQLite does not support FOR UPDATE so nothing to do.
    return $this;
  }
}