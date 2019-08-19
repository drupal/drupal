<?php

namespace Drupal\Tests\simpletest\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Fixture test that is executed during Simpletest UI testing.
 *
 * @see \Drupal\simpletest\Tests::testTestingThroughUI()
 *
 * @group simpletest
 * @group legacy
 */
class ThroughUITest extends BrowserTestBase {

  /**
   * This test method must always pass.
   */
  public function testThroughUi() {
    $this->pass('Success!');
  }

}
