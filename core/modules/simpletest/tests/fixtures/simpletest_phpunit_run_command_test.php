<?php

namespace Drupal\Tests\simpletest\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * This test crashes PHP.
 *
 * To avoid accidentally running, it is not in a normal PSR-4 directory, the
 * file name does not adhere to PSR-4 and an environment variable also needs to
 * be set for the crash to happen.
 */
class SimpletestPhpunitRunCommandTestWillDie extends UnitTestCase {

  public function testWillDie() {
    if (getenv('SimpletestPhpunitRunCommandTestWillDie') === 'fail') {
      exit(2);
    }
    $this->assertTrue(TRUE, 'Assertion to ensure test pass');
  }
}

