<?php

namespace Drupal\Tests\simpletest\Unit;

/**
 * This test crashes PHP.
 *
 * To avoid accidentally running, it is not in a normal PSR-4 directory, the
 * file name does not adhere to PSR-4 and an environment variable also needs to
 * be set for the crash to happen.
 *
 * @see \Drupal\Tests\simpletest\Unit\SimpletestPhpunitRunCommandTest::testSimpletestPhpUnitRunCommand()
 */
class SimpletestPhpunitRunCommandTestWillDie extends \PHPUnit_Framework_TestCase {

  /**
   * Performs the status specified by SimpletestPhpunitRunCommandTestWillDie.
   */
  public function testWillDie() {
    $status = (int) getenv('SimpletestPhpunitRunCommandTestWillDie');
    if ($status == 0) {
      $this->assertTrue(TRUE, 'Assertion to ensure test pass');
      return;
    }
    exit($status);
  }

}
