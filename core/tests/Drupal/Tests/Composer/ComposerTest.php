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
   * @covers ::ensureComposerVersion
   */
  public function testEnsureComposerVersion() {
    try {
      $this->assertNull(Composer::ensureComposerVersion());
    }
    catch (\RuntimeException $e) {
      $this->assertMatchesRegularExpression('/Drupal core development requires Composer 2.3.5, but Composer /', $e->getMessage());
    }
  }

  /**
   * Ensure that the configured php version matches the minimum php version.
   *
   * Also ensure that the minimum php version in the root-level composer.json
   * file exactly matches \Drupal::MINIMUM_PHP.
   */
  public function testEnsurePhpConfiguredVersion() {
    $composer_json = json_decode(file_get_contents($this->root . '/composer.json'), TRUE);
    $composer_core_json = json_decode(file_get_contents($this->root . '/core/composer.json'), TRUE);
    $this->assertEquals(\Drupal::MINIMUM_PHP, $composer_json['config']['platform']['php'], 'The \Drupal::MINIMUM_PHP constant should always be exactly the same as the config.platform.php in the root composer.json.');
    $this->assertEquals($composer_core_json['require']['php'], '>=' . $composer_json['config']['platform']['php'], 'The config.platform.php configured version in the root composer.json file should always be exactly the same as the minimum php version configured in core/composer.json.');
  }

}
