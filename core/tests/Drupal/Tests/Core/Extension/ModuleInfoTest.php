<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Extension;

// cspell:ignore nyan

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that core module info files have the expected keys.
 */
#[Group('Extension')]
class ModuleInfoTest extends UnitTestCase {

  use FileSystemModuleDiscoveryDataProviderTrait;

  /**
   * Tests that core module info files have the expected keys.
   */
  #[DataProvider('coreModuleListDataProvider')]
  public function testModuleInfo(string $module): void {
    $module_directory = __DIR__ . '/../../../../../modules/' . $module;
    $info = Yaml::decode(file_get_contents($module_directory . '/' . $module . '.info.yml'));
    $this->assertArrayHasKey('version', $info);
    $this->assertEquals('VERSION', $info['version']);
  }

  /**
   * Tests that non-hidden test modules use their expected package.
   */
  #[DataProvider('providerTestTestModulePackages')]
  public function testTestModulePackages(string $filename, string $expected_package): void {
    $info = Yaml::decode(file_get_contents($filename));
    $this->assertSame($expected_package, $info['package'] ?? NULL);
  }

  /**
   * Provides test module info files and their expected packages.
   */
  public static function providerTestTestModulePackages(): \Generator {
    $root = dirname(__DIR__, 6);
    // These modules deliberately use other packages to test module metadata.
    $expected_packages = [
      'config_mapping_test' => 'Core',
      'experimental_module_requirements_test' => 'Core (Experimental)',
      'experimental_module_test' => 'Core (Experimental)',
      'nyan_cat' => 'Core',
    ];

    $modules = (new ExtensionDiscovery($root))->scan('module', TRUE);
    foreach ($modules as $module) {
      if (!str_contains($module->getPath(), '/tests/modules/')) {
        continue;
      }
      $filename = $root . '/' . $module->getPathname();
      $info = Yaml::decode(file_get_contents($filename));
      if (!empty($info['hidden'])) {
        continue;
      }
      $package = $expected_packages[$module->getName()] ?? 'Testing';
      yield $module->getPathname() => [$filename, $package];
    }
  }

}
