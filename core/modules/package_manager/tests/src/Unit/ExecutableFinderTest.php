<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Unit;

use Drupal\package_manager\ExecutableFinder;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;

/**
 * @covers \Drupal\package_manager\ExecutableFinder
 * @group package_manager
 * @internal
 */
class ExecutableFinderTest extends UnitTestCase {

  /**
   * Tests that the executable finder looks for paths in configuration.
   */
  public function testCheckConfigurationForExecutablePath(): void {
    $config_factory = $this->getConfigFactoryStub([
      'package_manager.settings' => [
        'executables' => [
          'composer' => '/path/to/composer',
        ],
      ],
    ]);

    $decorated = $this->prophesize(ExecutableFinderInterface::class);
    $decorated->find('composer')->shouldNotBeCalled();
    $decorated->find('rsync')->shouldBeCalled();

    $finder = new ExecutableFinder($decorated->reveal(), $config_factory);
    $this->assertSame('/path/to/composer', $finder->find('composer'));
    $finder->find('rsync');
  }

  /**
   * Tests that the executable finder tries to use a local copy of Composer.
   */
  public function testComposerInstalledInProject(): void {
    vfsStream::setup('root', NULL, [
      'composer-path' => [
        'bin' => [],
      ],
    ]);
    $composer_path = 'vfs://root/composer-path/bin/composer';
    touch($composer_path);
    $this->assertFileExists($composer_path);

    $decorated = $this->prophesize(ExecutableFinderInterface::class);
    $decorated->find('composer')->willReturn('the real Composer');

    $finder = new ExecutableFinder(
      $decorated->reveal(),
      $this->getConfigFactoryStub([
        'package_manager.settings' => [
          'executables' => [],
        ],
      ]),
    );
    $reflector = new \ReflectionProperty($finder, 'composerPath');
    $reflector->setValue($finder, $composer_path);
    $this->assertSame($composer_path, $finder->find('composer'));

    // If the executable disappears, or Composer isn't locally installed, the
    // decorated executable finder should be called.
    unlink($composer_path);
    $this->assertSame('the real Composer', $finder->find('composer'));

    $reflector->setValue($finder, FALSE);
    $this->assertSame('the real Composer', $finder->find('composer'));
  }

}
