<?php

namespace Drupal\Tests\Composer;

use Drupal\Composer\Composer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Composer\Composer
 * @group Composer
 */
class ComposerTest extends UnitTestCase {

  /**
   * Verify that Composer::ensureComposerVersion() doesn't break.
   *
   * @covers::ensureComposerVersion
   */
  public function testEnsureComposerVersion() {
    try {
      $this->assertNull(Composer::ensureComposerVersion());
    }
    catch (\RuntimeException $e) {
      $this->assertRegExp('/Drupal core development requires Composer 1.9.0, but Composer /', $e->getMessage());
    }
  }

}
