<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Order;

/**
 * Set this implementation to be after others.
 */
readonly class OrderAfter extends RelativeOrderBase {

  /**
   * {@inheritdoc}
   */
  protected function isAfter(): bool {
    return TRUE;
  }

}
