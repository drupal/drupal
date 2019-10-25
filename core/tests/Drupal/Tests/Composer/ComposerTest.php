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

  /**
   * Verify that Composer::ensureBehatDriverVersions() detects a good version.
   *
   * @covers::ensureBehatDriverVersions
   */
  public function testEnsureBehatDriverVersions() {
    // First call 'ensureBehatDriverVersions' test directly using Drupal's
    // composer.lock. It should not fail.
    chdir($this->root);
    $this->assertNull(Composer::ensureBehatDriverVersions());

    // Next, call 'ensureBehatDriverVersions' again, this time using a fixture
    // with a known-bad version number.
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageRegExp('#^Drupal requires behat/mink-selenium2-driver:1.3.x-dev#');
    chdir(__DIR__ . '/fixtures/ensureBehatDriverVersionsFixture');
    $this->assertNull(Composer::ensureBehatDriverVersions());
  }

}
