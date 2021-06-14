<?php

namespace Drupal\Tests\Composer\Plugin\VendorHardening;

use Composer\Package\RootPackageInterface;
use Drupal\Composer\Plugin\VendorHardening\Config;
use Drupal\Tests\Traits\PhpUnitWarnings;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Drupal\Composer\Plugin\VendorHardening\Config
 * @group VendorHardening
 */
class ConfigTest extends TestCase {

  use PhpUnitWarnings;

  /**
   * @covers ::getPathsForPackage
   */
  public function testGetPathsForPackageMixedCase() {
    $config = $this->getMockBuilder(Config::class)
      ->setMethods(['getAllCleanupPaths'])
      ->disableOriginalConstructor()
      ->getMock();

    $config->expects($this->once())
      ->method('getAllCleanupPaths')
      ->willReturn(['package' => ['path']]);

    $this->assertSame(['path'], $config->getPathsForPackage('pACKage'));
  }

  /**
   * @covers ::getAllCleanupPaths
   */
  public function testNoRootMergeConfig() {
    // Root package has no extra field.
    $root = $this->getMockBuilder(RootPackageInterface::class)
      ->setMethods(['getExtra'])
      ->getMockForAbstractClass();
    $root->expects($this->once())
      ->method('getExtra')
      ->willReturn([]);

    $config = new Config($root);

    $ref_default = new \ReflectionProperty($config, 'defaultConfig');
    $ref_default->setAccessible(TRUE);

    $ref_plugin_config = new \ReflectionMethod($config, 'getAllCleanupPaths');
    $ref_plugin_config->setAccessible(TRUE);

    $this->assertEquals(
      $ref_default->getValue($config), $ref_plugin_config->invoke($config)
    );
  }

  /**
   * @covers ::getAllCleanupPaths
   */
  public function testRootMergeConfig() {
    // Root package has configuration in extra.
    $root = $this->getMockBuilder(RootPackageInterface::class)
      ->setMethods(['getExtra'])
      ->getMockForAbstractClass();
    $root->expects($this->once())
      ->method('getExtra')
      ->willReturn([
        'drupal-core-vendor-hardening' => [
          'isa/string' => 'test_dir',
          'an/array' => ['test_dir', 'doc_dir'],
        ],
      ]);

    $config = new Config($root);

    $ref_plugin_config = new \ReflectionMethod($config, 'getAllCleanupPaths');
    $ref_plugin_config->setAccessible(TRUE);

    $plugin_config = $ref_plugin_config->invoke($config);

    $this->assertSame(['test_dir'], $plugin_config['isa/string']);
    $this->assertSame(['test_dir', 'doc_dir'], $plugin_config['an/array']);
  }

  /**
   * @covers ::getAllCleanupPaths
   */
  public function testMixedCaseConfigCleanupPackages() {
    // Root package has configuration in extra.
    $root = $this->getMockBuilder(RootPackageInterface::class)
      ->setMethods(['getExtra'])
      ->getMockForAbstractClass();
    $root->expects($this->once())
      ->method('getExtra')
      ->willReturn([
        'drupal-core-vendor-hardening' => [
          'NotMikey179/vfsStream' => ['src/test'],
        ],
      ]);

    $config = new Config($root);

    $ref_plugin_config = new \ReflectionMethod($config, 'getAllCleanupPaths');
    $ref_plugin_config->setAccessible(TRUE);

    // Put some mixed-case in the defaults.
    $ref_default = new \ReflectionProperty($config, 'defaultConfig');
    $ref_default->setAccessible(TRUE);
    $ref_default->setValue($config, [
      'BeHatted/Mank' => ['tests'],
      'SymFunic/HTTPFoundational' => ['src'],
    ]);

    $plugin_config = $ref_plugin_config->invoke($config);

    foreach (array_keys($plugin_config) as $package_name) {
      $this->assertDoesNotMatchRegularExpression('/[A-Z]/', $package_name);
    }
  }

}
