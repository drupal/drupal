<?php

/**
 * @file
 * Contains \Drupal\Tests\Listeners\SafeMarkupSideEffects.
 *
 * Listener for PHPUnit tests, to enforce that data providers don't add to the
 * SafeMarkup static safe string list.
 */

namespace Drupal\Tests\Listeners;

use Drupal\Component\Utility\SafeMarkup;

/**
 * Listens for PHPUnit tests and fails those with SafeMarkup side effects.
 */
class SafeMarkupSideEffects extends \PHPUnit_Framework_BaseTestListener {

  /**
   * {@inheritdoc}
   */
  public function startTestSuite(\PHPUnit_Framework_TestSuite $suite) {
    // Use a static so we only do this test once after all the data providers
    // have run.
    static $tested = FALSE;
    if ($suite->getName() !== '' && !$tested) {
      $tested = TRUE;
      if (!empty(SafeMarkup::getAll())) {
        throw new \RuntimeException('SafeMarkup string list polluted by data providers');
      }
    }
  }

}
