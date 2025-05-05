<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Order;

/**
 * Set this implementation to be before others.
 */
readonly class OrderBefore extends RelativeOrderBase {

  /**
   * {@inheritdoc}
   */
  protected function isAfter(): bool {
    return FALSE;
  }

}
