<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer\Plugin\VendorHardening;

use Composer\Package\RootPackageInterface;
use Drupal\Composer\Plugin\VendorHardening\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

/**
 * Tests Drupal\Composer\Plugin\VendorHardening\Config.
 */
#[CoversClass(Config::class)]
#[Group('VendorHardening')]
class ConfigTest extends TestCase {

  /**
   * Tests get paths for package mixed case.
   */
  public function testGetPathsForPackageMixedCase(): void {
    $config = $this->getMockBuilder(Config::class)
      ->onlyMethods(['getAllCleanupPaths'])
      ->disableOriginalConstructor()
      ->getMock();

    $config->expects($this->once())
      ->method('getAllCleanupPaths')
      ->willReturn(['package' => ['path']]);

    $this->assertSame(['path'], $config->getPathsForPackage('pACKage'));
  }

  /**
   * Tests no root merge config.
   *
   * @legacy-covers ::getAllCleanupPaths
   */
  public function testNoRootMergeConfig(): void {
    // Root package has no extra field.
    $root = $this->createMock(RootPackageInterface::class);
    $root->expects($this->once())
      ->method('getExtra')
      ->willReturn([]);

    $config = new Config($root);

    $ref_default = new \ReflectionProperty($config, 'defaultConfig');

    $ref_plugin_config = new \ReflectionMethod($config, 'getAllCleanupPaths');

    $this->assertEquals(
      $ref_default->getValue($config), $ref_plugin_config->invoke($config)
    );
  }

  /**
   * Tests root merge config.
   *
   * @legacy-covers ::getAllCleanupPaths
   */
  public function testRootMergeConfig(): void {
    // Root package has configuration in extra.
    $root = $this->createMock(RootPackageInterface::class);
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

    $plugin_config = $ref_plugin_config->invoke($config);

    $this->assertSame(['test_dir'], $plugin_config['isa/string']);
    $this->assertSame(['test_dir', 'doc_dir'], $plugin_config['an/array']);
  }

  /**
   * Tests mixed case config cleanup packages.
   *
   * @legacy-covers ::getAllCleanupPaths
   */
  #[RunInSeparateProcess]
  public function testMixedCaseConfigCleanupPackages(): void {
    // Root package has configuration in extra.
    $root = $this->createMock(RootPackageInterface::class);
    $root->expects($this->once())
      ->method('getExtra')
      ->willReturn([
        'drupal-core-vendor-hardening' => [
          'NotMikey179/vfsStream' => ['src/test'],
        ],
      ]);

    $config = new Config($root);

    $ref_plugin_config = new \ReflectionMethod($config, 'getAllCleanupPaths');

    // Put some mixed-case in the defaults.
    $ref_default = new \ReflectionProperty($config, 'defaultConfig');
    $ref_default->setValue($config, [
      'BeHatted/Monk' => ['tests'],
      'SymPhony/HTTPFoundational' => ['src'],
    ]);

    $plugin_config = $ref_plugin_config->invoke($config);

    foreach (array_keys($plugin_config) as $package_name) {
      $this->assertDoesNotMatchRegularExpression('/[A-Z]/', $package_name);
    }
  }

  /**
   * Tests skip clean.
   *
   * @legacy-covers ::getAllCleanupPaths
   */
  public function testSkipClean(): void {
    $root = $this->createMock(RootPackageInterface::class);
    $root->expects($this->once())
      ->method('getExtra')
      ->willReturn([
        'drupal-core-vendor-hardening' => [
          'composer/composer' => FALSE,
        ],
      ]);

    $plugin_config = (new Config($root))->getAllCleanupPaths();
    $this->assertArrayNotHasKey('composer/composer', $plugin_config);
  }

}
