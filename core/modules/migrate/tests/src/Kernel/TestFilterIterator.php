<?php

namespace Drupal\Tests\migrate\Kernel;

/**
 * Class to test FilterIterators.
 */
class TestFilterIterator extends \FilterIterator {

  /**
   * {@inheritdoc}
   */
  public function accept() {
    return TRUE;
  }

}
