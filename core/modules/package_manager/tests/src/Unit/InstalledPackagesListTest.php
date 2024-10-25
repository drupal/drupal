<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Unit;

use Drupal\package_manager\InstalledPackage;
use Drupal\package_manager\InstalledPackagesList;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\package_manager\InstalledPackagesList
 *
 * @group package_manager
 */
class InstalledPackagesListTest extends UnitTestCase {

  /**
   * @covers ::offsetSet
   * @covers ::offsetUnset
   * @covers ::append
   * @covers ::exchangeArray
   *
   * @testWith ["offsetSet", ["new", "thing"]]
   *   ["offsetUnset", ["existing"]]
   *   ["append", ["new thing"]]
   *   ["exchangeArray", [{"evil": "twin"}]]
   */
  public function testImmutability(string $method, array $arguments): void {
    $list = new InstalledPackagesList(['existing' => 'thing']);
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Installed package lists cannot be modified.');
    $list->$method(...$arguments);
  }

  /**
   * @covers ::getPackagesNotIn
   * @covers ::getPackagesWithDifferentVersionsIn
   */
  public function testPackageComparison(): void {
    $active = new InstalledPackagesList([
      'drupal/existing' => InstalledPackage::createFromArray([
        'name' => 'drupal/existing',
        'version' => '1.0.0',
        'path' => __DIR__,
        'type' => 'drupal-module',
      ]),
      'drupal/updated' => InstalledPackage::createFromArray([
        'name' => 'drupal/updated',
        'version' => '1.0.0',
        'path' => __DIR__,
        'type' => 'drupal-module',
      ]),
      'drupal/removed' => InstalledPackage::createFromArray([
        'name' => 'drupal/removed',
        'version' => '1.0.0',
        'path' => __DIR__,
        'type' => 'drupal-module',
      ]),
    ]);
    $staged = new InstalledPackagesList([
      'drupal/existing' => InstalledPackage::createFromArray([
        'name' => 'drupal/existing',
        'version' => '1.0.0',
        'path' => __DIR__,
        'type' => 'drupal-module',
      ]),
      'drupal/updated' => InstalledPackage::createFromArray([
        'name' => 'drupal/updated',
        'version' => '1.1.0',
        'path' => __DIR__,
        'type' => 'drupal-module',
      ]),
      'drupal/added' => InstalledPackage::createFromArray([
        'name' => 'drupal/added',
        'version' => '1.0.0',
        'path' => __DIR__,
        'type' => 'drupal-module',
      ]),
    ]);

    $added = $staged->getPackagesNotIn($active)->getArrayCopy();
    $this->assertSame(['drupal/added'], array_keys($added));

    $removed = $active->getPackagesNotIn($staged)->getArrayCopy();
    $this->assertSame(['drupal/removed'], array_keys($removed));

    $updated = $active->getPackagesWithDifferentVersionsIn($staged)->getArrayCopy();
    $this->assertSame(['drupal/updated'], array_keys($updated));
  }

  /**
   * @covers ::getCorePackages
   */
  public function testCorePackages(): void {
    $data = [
      'drupal/core' => InstalledPackage::createFromArray([
        'name' => 'drupal/core',
        'version' => \Drupal::VERSION,
        'type' => 'drupal-core',
        'path' => __DIR__,
      ]),
      'drupal/core-dev' => InstalledPackage::createFromArray([
        'name' => 'drupal/core-dev',
        'version' => \Drupal::VERSION,
        'type' => 'metapackage',
        'path' => NULL,
      ]),
      'drupal/core-dev-pinned' => InstalledPackage::createFromArray([
        'name' => 'drupal/core-dev-pinned',
        'version' => \Drupal::VERSION,
        'type' => 'metapackage',
        'path' => NULL,
      ]),
      'drupal/core-composer-scaffold' => InstalledPackage::createFromArray([
        'name' => 'drupal/core-composer-scaffold',
        'version' => \Drupal::VERSION,
        'type' => 'composer-plugin',
        'path' => __DIR__,
      ]),
      'drupal/core-project-message' => [
        'name' => 'drupal/core-project-message',
        'version' => \Drupal::VERSION,
        'type' => 'composer-plugin',
        'path' => __DIR__,
      ],
      'drupal/core-vendor-hardening' => InstalledPackage::createFromArray([
        'name' => 'drupal/core-vendor-hardening',
        'version' => \Drupal::VERSION,
        'type' => 'composer-plugin',
        'path' => __DIR__,
      ]),
      'drupal/not-core' => InstalledPackage::createFromArray([
        'name' => 'drupal/not-core',
        'version' => '1.0.0',
        'type' => 'drupal-module',
        'path' => __DIR__,
      ]),
    ];

    $list = new InstalledPackagesList($data);
    $this->assertArrayNotHasKey('drupal/not-core', $list->getCorePackages());

    // Tests that we don't get core packages intended for development when
    // include_dev is set to FALSE.
    $core_packages_no_dev = $list->getCorePackages(FALSE);
    $this->assertArrayNotHasKey('drupal/core-dev', $core_packages_no_dev);
    $this->assertArrayNotHasKey('drupal/core-dev-pinned', $core_packages_no_dev);
    // We still get other packages as intended.
    $this->assertArrayHasKey('drupal/core', $core_packages_no_dev);

    // If drupal/core-recommended is in the list, it should supersede
    // drupal/core.
    $this->assertArrayHasKey('drupal/core', $list->getCorePackages());
    $data['drupal/core-recommended'] = InstalledPackage::createFromArray([
      'name' => 'drupal/core-recommended',
      'version' => \Drupal::VERSION,
      'type' => 'metapackage',
      'path' => NULL,
    ]);
    $list = new InstalledPackagesList($data);
    $this->assertArrayNotHasKey('drupal/core', $list->getCorePackages());
  }

}
