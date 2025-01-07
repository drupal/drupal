<?php

declare(strict_types=1);

// cspell:ignore dummydb

namespace Drupal\dummydb\Driver\Database\dummydb\Install;

use Drupal\mysql\Driver\Database\mysql\Install\Tasks as CoreTasks;

/**
 * Specifies installation tasks for DummyDB test database.
 */
class Tasks extends CoreTasks {

  /**
   * {@inheritdoc}
   */
  public function name() {
    return $this->t('DummyDB');
  }

}
