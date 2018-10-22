<?php

namespace Drupal\KernelTests\Core\Common;

use Drupal\Core\Extension\Dependency;
use Drupal\KernelTests\KernelTestBase;

/**
 * Parse a predefined amount of bytes and compare the output with the expected
 * value.
 *
 * @group Common
 * @group legacy
 */
class DrupalCheckIncompatibilityTest extends KernelTestBase {

  /**
   * Tests drupal_check_incompatibility().
   *
   * @dataProvider providerDrupalCheckIncompatibility
   * @expectedDeprecation drupal_check_incompatibility() is deprecated. Use \Drupal\Core\Extension\Dependency::isCompatible() instead. See https://www.drupal.org/node/2756875
   */
  public function testDrupalCheckIncompatibility($version_info, $version_to_check, $result) {
    $this->assertSame($result, drupal_check_incompatibility($version_info, $version_to_check));
  }

  /**
   * Data provider for testDrupalCheckIncompatibility.
   */
  public function providerDrupalCheckIncompatibility() {
    $module_data = [
      'name' => 'views_ui',
      'original_version' => ' (8.x-1.0)',
      'versions' => [['op' => '=', 'version' => '1.0']],
    ];

    $data = [];
    $data['is compatible'] = [$module_data, '1.0', NULL];
    $data['not compatible'] = [$module_data, '1.1', ' (8.x-1.0)'];
    // Prove that the BC layer using ArrayAccess works with
    // drupal_check_incompatibility().
    $dependency = new Dependency('views', 'drupal', '8.x-1.2');
    $data['dependency object'] = [$dependency, '1.1', ' (8.x-1.2)'];
    return $data;
  }

}
