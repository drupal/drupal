<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Extension;

use Drupal\Component\Serialization\Yaml;
use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests that core module info files have the expected keys.
 *
 * @group Extension
 */
class ModuleInfoTest extends UnitTestCase {

  use FileSystemModuleDiscoveryDataProviderTrait;

  /**
   * Tests that core module info files have the expected keys.
   *
   * @dataProvider coreModuleListDataProvider
   */
  public function testModuleInfo($module) {
    $module_directory = __DIR__ . '/../../../../../modules/' . $module;
    $info = Yaml::decode(file_get_contents($module_directory . '/' . $module . '.info.yml'));
    $this->assertArrayHasKey('version', $info);
    $this->assertEquals('VERSION', $info['version']);
  }

}
