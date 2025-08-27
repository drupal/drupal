<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Unit;

use Drupal\package_manager\InstalledPackage;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\package_manager\InstalledPackage.
 */
#[CoversClass(InstalledPackage::class)]
#[Group('package_manager')]
class InstalledPackageTest extends UnitTestCase {

  /**
   * Tests path resolution.
   *
   * @legacy-covers ::createFromArray
   */
  #[Depends('testMetapackageWithAPath')]
  public function testPathResolution(): void {
    // Metapackages must be created without a path.
    $package = InstalledPackage::createFromArray([
      'name' => 'vendor/test',
      'type' => 'metapackage',
      'version' => '1.0.0',
      'path' => NULL,
    ]);
    $this->assertNull($package->path);

    // Paths should be converted to real paths.
    $package = InstalledPackage::createFromArray([
      'name' => 'vendor/test',
      'type' => 'library',
      'version' => '1.0.0',
      'path' => __DIR__ . '/..',
    ]);
    $this->assertSame(realpath(__DIR__ . '/..'), $package->path);

    // If we provide a path that cannot be resolved to a real path, it should
    // raise an error.
    $this->expectException(\TypeError::class);
    InstalledPackage::createFromArray([
      'name' => 'vendor/test',
      'type' => 'library',
      'version' => '1.0.0',
      'path' => $this->getRandomGenerator()->string(),
    ]);
  }

  /**
   * Tests metapackage with a path.
   *
   * @legacy-covers ::createFromArray
   */
  public function testMetapackageWithAPath(): void {
    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage('Metapackage install path must be NULL.');

    InstalledPackage::createFromArray([
      'name' => 'vendor/test',
      'type' => 'metapackage',
      'version' => '1.0.0',
      'path' => __DIR__,
    ]);
  }

}
