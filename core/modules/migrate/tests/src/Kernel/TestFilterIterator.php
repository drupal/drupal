<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel;

/**
 * Class to test FilterIterators.
 */
class TestFilterIterator extends \FilterIterator {

  /**
   * {@inheritdoc}
   */
  public function accept(): bool {
    return TRUE;
  }

}
