<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Traits;

use Drupal\package_manager\InstalledPackage;
use Drupal\package_manager\InstalledPackagesList;

/**
 * A trait for comparing InstalledPackagesList objects.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
trait InstalledPackagesListTrait {

  /**
   * Asserts that 2 installed package lists are equal.
   *
   * @param \Drupal\package_manager\InstalledPackagesList $expected_list
   *   The expected list.
   * @param \Drupal\package_manager\InstalledPackagesList $actual_list
   *   The actual list.
   */
  private function assertPackageListsEqual(InstalledPackagesList $expected_list, InstalledPackagesList $actual_list): void {
    $expected_array = $expected_list->getArrayCopy();
    $actual_array = $actual_list->getArrayCopy();
    ksort($expected_array);
    ksort($actual_array);
    $this->assertSame(array_keys($expected_array), array_keys($actual_array));
    foreach ($expected_list as $package_name => $expected_package) {
      $this->assertInstanceOf(InstalledPackage::class, $expected_package);
      $actual_package = $actual_list[$package_name];
      $this->assertInstanceOf(InstalledPackage::class, $actual_package);
      $this->assertSame($expected_package->name, $actual_package->name);
      $this->assertSame($expected_package->version, $actual_package->version);
      $this->assertSame($expected_package->path, $actual_package->path);
      $this->assertSame($expected_package->type, $actual_package->type);
      $this->assertSame($expected_package->getProjectName(), $actual_package->getProjectName());
    }
  }

}
