<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer\Plugin\VendorHardening;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Drupal\Composer\Plugin\VendorHardening\Config;
use Drupal\Composer\Plugin\VendorHardening\VendorHardeningPlugin;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \Drupal\Composer\Plugin\VendorHardening\VendorHardeningPlugin
 * @group VendorHardening
 */
class VendorHardeningPluginTest extends TestCase {

  use ProphecyTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    vfsStream::setup('vendor', NULL, [
      'drupal' => [
        'package' => [
          'tests' => [
            'SomeTest.php' => '<?php',
          ],
          'SomeFile.php' => '<?php',
        ],
      ],
    ]);
  }

  /**
   * @covers ::cleanPackage
   */
  public function testCleanPackage(): void {

    $config = $this->getMockBuilder(Config::class)
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->once())
      ->method('getPathsForPackage')
      ->willReturn(['tests']);

    $plugin = $this->getMockBuilder(VendorHardeningPlugin::class)
      ->onlyMethods(['getInstallPathForPackage'])
      ->getMock();
    $plugin->expects($this->once())
      ->method('getInstallPathForPackage')
      ->willReturn(vfsStream::url('vendor/drupal/package'));

    $ref_config = new \ReflectionProperty($plugin, 'config');
    $ref_config->setValue($plugin, $config);

    $io = $this->prophesize(IOInterface::class);
    $ref_io = new \ReflectionProperty($plugin, 'io');
    $ref_io->setValue($plugin, $io->reveal());

    $this->assertFileExists(vfsStream::url('vendor/drupal/package/tests/SomeTest.php'));

    $package = $this->prophesize(PackageInterface::class);
    $package->getName()->willReturn('drupal/package');

    $plugin->cleanPackage($package->reveal());

    $this->assertFileDoesNotExist(vfsStream::url('vendor/drupal/package/tests'));
  }

  /**
   * @covers ::cleanPathsForPackage
   */
  public function testCleanPathsForPackage(): void {
    $plugin = $this->getMockBuilder(VendorHardeningPlugin::class)
      ->onlyMethods(['getInstallPathForPackage'])
      ->getMock();
    $plugin->expects($this->once())
      ->method('getInstallPathForPackage')
      ->willReturn(vfsStream::url('vendor/drupal/package'));

    $io = $this->prophesize(IOInterface::class);
    $ref_io = new \ReflectionProperty($plugin, 'io');
    $ref_io->setValue($plugin, $io->reveal());

    $this->assertFileExists(vfsStream::url('vendor/drupal/package/tests/SomeTest.php'));
    $this->assertFileExists(vfsStream::url('vendor/drupal/package/SomeFile.php'));

    $package = $this->prophesize(PackageInterface::class);
    $package->getName()->willReturn('drupal/package');

    $ref_clean = new \ReflectionMethod($plugin, 'cleanPathsForPackage');
    $ref_clean->invokeArgs($plugin, [$package->reveal(), ['tests', 'SomeFile.php']]);

    $this->assertFileDoesNotExist(vfsStream::url('vendor/drupal/package/tests'));
    $this->assertFileDoesNotExist(vfsStream::url('vendor/drupal/package/SomeFile.php'));
  }

  /**
   * @covers ::cleanAllPackages
   */
  public function testCleanAllPackages(): void {
    $config = $this->getMockBuilder(Config::class)
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->once())
      ->method('getAllCleanupPaths')
      ->willReturn(['drupal/package' => ['tests']]);

    $package = $this->createMock(PackageInterface::class);
    $package->expects($this->any())
      ->method('getName')
      ->willReturn('drupal/package');

    $plugin = $this->getMockBuilder(VendorHardeningPlugin::class)
      ->onlyMethods(['getInstalledPackages', 'getInstallPathForPackage'])
      ->getMock();
    $plugin->expects($this->once())
      ->method('getInstalledPackages')
      ->willReturn([$package]);
    $plugin->expects($this->once())
      ->method('getInstallPathForPackage')
      ->willReturn(vfsStream::url('vendor/drupal/package'));

    $io = $this->prophesize(IOInterface::class);
    $ref_io = new \ReflectionProperty($plugin, 'io');
    $ref_io->setValue($plugin, $io->reveal());

    $ref_config = new \ReflectionProperty($plugin, 'config');
    $ref_config->setValue($plugin, $config);

    $this->assertFileExists(vfsStream::url('vendor/drupal/package/tests/SomeTest.php'));

    $plugin->cleanAllPackages();

    $this->assertFileDoesNotExist(vfsStream::url('vendor/drupal/package/tests'));
  }

  /**
   * @covers ::writeAccessRestrictionFiles
   */
  public function testWriteAccessRestrictionFiles(): void {
    $dir = vfsStream::url('vendor');

    // Set up mocks so that writeAccessRestrictionFiles() can eventually use
    // the IOInterface object.
    $composer = $this->getMockBuilder(Composer::class)
      ->onlyMethods(['getPackage'])
      ->getMock();
    $composer->expects($this->once())
      ->method('getPackage')
      ->willReturn($this->prophesize(RootPackageInterface::class)->reveal());

    $plugin = new VendorHardeningPlugin();
    $plugin->activate($composer, $this->prophesize(IOInterface::class)->reveal());

    $this->assertDirectoryExists($dir);

    $this->assertFileDoesNotExist($dir . '/.htaccess');

    $plugin->writeAccessRestrictionFiles($dir);

    $this->assertFileExists($dir . '/.htaccess');
  }

  public static function providerFindBinOverlap() {
    return [
      [
        [],
        ['bin/script'],
        ['tests'],
      ],
      [
        ['bin/composer' => 'bin/composer'],
        ['bin/composer'],
        ['bin', 'tests'],
      ],
      [
        ['bin/composer' => 'bin/composer'],
        ['bin/composer'],
        ['bin/composer'],
      ],
      [
        [],
        ['bin/composer'],
        ['bin/something_else'],
      ],
      [
        [],
        ['test/script'],
        ['test/longer'],
      ],
      [
        ['bin/very/long/path/script' => 'bin/very/long/path/script'],
        ['bin/very/long/path/script'],
        ['bin'],
      ],
      [
        ['bin/bin/bin' => 'bin/bin/bin'],
        ['bin/bin/bin'],
        ['bin/bin'],
      ],
      [
        [],
        ['bin/bin'],
        ['bin/bin/bin'],
      ],
    ];
  }

  /**
   * @covers ::findBinOverlap
   * @dataProvider providerFindBinOverlap
   */
  public function testFindBinOverlap($expected, $binaries, $clean_paths): void {
    $plugin = $this->getMockBuilder(VendorHardeningPlugin::class)
      ->disableOriginalConstructor()
      ->getMock();

    $ref_find_bin_overlap = new \ReflectionMethod($plugin, 'findBinOverlap');

    $this->assertSame($expected, $ref_find_bin_overlap->invokeArgs($plugin, [$binaries, $clean_paths]));
  }

}
