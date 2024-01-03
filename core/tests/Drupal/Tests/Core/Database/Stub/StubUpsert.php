<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Database\Stub;

use Drupal\Core\Database\Query\Upsert;

/**
 * A stub of core Upsert for testing purposes.
 */
class StubUpsert extends Upsert {

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return '';
  }

}
