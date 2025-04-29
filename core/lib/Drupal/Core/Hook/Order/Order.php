<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Order;

use Drupal\Core\Hook\OrderOperation\FirstOrLast;
use Drupal\Core\Hook\OrderOperation\OrderOperation;

/**
 * Set this implementation to be first or last.
 */
enum Order: int implements OrderInterface {

  // This implementation should execute first.
  case First = 1;

  // This implementation should execute last.
  case Last = 0;

  /**
   * {@inheritdoc}
   */
  public function getOperation(string $identifier): OrderOperation {
    return new FirstOrLast($identifier, $this === self::Last);
  }

}
