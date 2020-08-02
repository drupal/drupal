<?php

namespace Drupal\Tests\Composer\Plugin\VendorHardening;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Drupal\Composer\Plugin\VendorHardening\Config;
use Drupal\Composer\Plugin\VendorHardening\VendorHardeningPlugin;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Composer\Plugin\VendorHardening\VendorHardeningPlugin
 * @group VendorHardening
 */
class VendorHardeningPluginTest extends TestCase {

  public function setUp(): void {
    parent::setUp();
    vfsStream::setup('vendor', NULL, [
      'drupal' => [
        'package' => [
          'tests' => [
            'SomeTest.php' => '<?php',
          ],
        ],
      ],
    ]);
  }

  /**
   * @covers ::cleanPackage
   */
  public function testCleanPackage() {

    $config = $this->getMockBuilder(Config::class)
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->once())
      ->method('getPathsForPackage')
      ->willReturn(['tests']);

    $plugin = new VendorHardeningPlugin();
    $ref_config = new \ReflectionProperty($plugin, 'config');
    $ref_config->setAccessible(TRUE);
    $ref_config->setValue($plugin, $config);

    $io = $this->prophesize(IOInterface::class);
    $ref_io = new \ReflectionProperty($plugin, 'io');
    $ref_io->setAccessible(TRUE);
    $ref_io->setValue($plugin, $io->reveal());

    $this->assertFileExists(vfsStream::url('vendor/drupal/package/tests/SomeTest.php'));

    $plugin->cleanPackage(vfsStream::url('vendor'), 'drupal/package');

    $this->assertFileNotExists(vfsStream::url('vendor/drupal/package/tests'));
  }

  /**
   * @covers ::cleanPathsForPackage
   */
  public function testCleanPathsForPackage() {
    $plugin = new VendorHardeningPlugin();

    $io = $this->prophesize(IOInterface::class);
    $ref_io = new \ReflectionProperty($plugin, 'io');
    $ref_io->setAccessible(TRUE);
    $ref_io->setValue($plugin, $io->reveal());

    $this->assertFileExists(vfsStream::url('vendor/drupal/package/tests/SomeTest.php'));

    $ref_clean = new \ReflectionMethod($plugin, 'cleanPathsForPackage');
    $ref_clean->setAccessible(TRUE);
    $ref_clean->invokeArgs($plugin, [vfsStream::url('vendor'), 'drupal/package', ['tests']]);

    $this->assertFileNotExists(vfsStream::url('vendor/drupal/package/tests'));
  }

  /**
   * @covers ::cleanAllPackages
   */
  public function testCleanAllPackages() {
    $config = $this->getMockBuilder(Config::class)
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->once())
      ->method('getAllCleanupPaths')
      ->willReturn(['drupal/package' => ['tests']]);

    $package = $this->getMockBuilder(PackageInterface::class)
      ->getMockForAbstractClass();
    $package->expects($this->any())
      ->method('getName')
      ->willReturn('drupal/package');

    $plugin = $this->getMockBuilder(VendorHardeningPlugin::class)
      ->setMethods(['getInstalledPackages'])
      ->getMock();
    $plugin->expects($this->once())
      ->method('getInstalledPackages')
      ->willReturn([$package]);

    $io = $this->prophesize(IOInterface::class);
    $ref_io = new \ReflectionProperty($plugin, 'io');
    $ref_io->setAccessible(TRUE);
    $ref_io->setValue($plugin, $io->reveal());

    $ref_config = new \ReflectionProperty($plugin, 'config');
    $ref_config->setAccessible(TRUE);
    $ref_config->setValue($plugin, $config);

    $this->assertFileExists(vfsStream::url('vendor/drupal/package/tests/SomeTest.php'));

    $plugin->cleanAllPackages(vfsStream::url('vendor'));

    $this->assertFileNotExists(vfsStream::url('vendor/drupal/package/tests'));
  }

  /**
   * @covers ::writeAccessRestrictionFiles
   */
  public function testWriteAccessRestrictionFiles() {
    $dir = vfsStream::url('vendor');

    // Set up mocks so that writeAccessRestrictionFiles() can eventually use
    // the IOInterface object.
    $composer = $this->getMockBuilder(Composer::class)
      ->setMethods(['getPackage'])
      ->getMock();
    $composer->expects($this->once())
      ->method('getPackage')
      ->willReturn($this->prophesize(RootPackageInterface::class)->reveal());

    $plugin = new VendorHardeningPlugin();
    $plugin->activate($composer, $this->prophesize(IOInterface::class)->reveal());

    $this->assertDirectoryExists($dir);

    $this->assertFileNotExists($dir . '/.htaccess');
    $this->assertFileNotExists($dir . '/web.config');

    $plugin->writeAccessRestrictionFiles($dir);

    $this->assertFileExists($dir . '/.htaccess');
    $this->assertFileExists($dir . '/web.config');
  }

  public function providerFindBinOverlap() {
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
  public function testFindBinOverlap($expected, $binaries, $clean_paths) {
    $plugin = $this->getMockBuilder(VendorHardeningPlugin::class)
      ->disableOriginalConstructor()
      ->getMock();

    $ref_find_bin_overlap = new \ReflectionMethod($plugin, 'findBinOverlap');
    $ref_find_bin_overlap->setAccessible(TRUE);

    $this->assertSame($expected, $ref_find_bin_overlap->invokeArgs($plugin, [$binaries, $clean_paths]));
  }

}
