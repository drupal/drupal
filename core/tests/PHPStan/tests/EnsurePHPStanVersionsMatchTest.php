<?php

declare(strict_types=1);

namespace Drupal\PHPStan\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests that PHPStan version used for rules testing matches core.
 */
class EnsurePHPStanVersionsMatchTest extends TestCase {

  public function testVersions(): void {
    $test_composer = json_decode(file_get_contents(__DIR__ . '/../composer.json'), TRUE);
    $drupal_composer = json_decode(file_get_contents(__DIR__ . '/../../../../composer/Metapackage/PinnedDevDependencies/composer.json'), TRUE);
    $this->assertSame($test_composer['require-dev']['phpstan/phpstan'], $drupal_composer['require']['phpstan/phpstan']);
  }

}
