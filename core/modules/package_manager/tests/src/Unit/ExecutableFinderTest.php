<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Unit;

use ColinODell\PsrTestLogger\TestLogger;
use Drupal\Component\Serialization\Json;
use Drupal\Core\File\FileSystemInterface;
use Drupal\package_manager\ExecutableFinder;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use PHPUnit\Framework\Attributes\TestWith;

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

    $finder = new ExecutableFinder(
      $decorated->reveal(),
      $config_factory,
      $this->prophesize(FileSystemInterface::class)->reveal(),
    );
    $this->assertSame('/path/to/composer', $finder->find('composer'));
    $finder->find('rsync');
  }

  /**
   * Tests that the executable finder tries to use a local copy of Composer.
   *
   * @param bool $chmod_result
   *   Whether the Composer binary will be successfully made read-only.
   */
  #[TestWith([TRUE])]
  #[TestWith([FALSE])]
  public function testComposerInstalledInProject(bool $chmod_result): void {
    vfsStream::setup('root', NULL, [
      'composer-path' => [
        'bin' => [
          'composer' => 'A fake Composer executable',
        ],
        'composer.json' => Json::encode([
          'bin' => ['bin/composer'],
        ]),
      ],
    ]);
    $composer_bin = 'vfs://root/composer-path/bin/composer';
    $this->assertTrue(chmod($composer_bin, 0755));

    $decorated = $this->prophesize(ExecutableFinderInterface::class);
    $decorated->find('composer')->willReturn('the real Composer');

    // The Composer binary is executable and should be made read-only.
    $file_system = $this->prophesize(FileSystemInterface::class);
    $file_system->chmod($composer_bin, 0644)
      ->willReturn($chmod_result)
      ->shouldBeCalled();

    $finder = new ExecutableFinder(
      $decorated->reveal(),
      $this->getConfigFactoryStub([
        'package_manager.settings' => [
          'executables' => [],
        ],
      ]),
      $file_system->reveal(),
    );

    $logger = new TestLogger();
    $finder->setLogger($logger);

    $reflector = new \ReflectionProperty($finder, 'composerPackagePath');
    $reflector->setValue($finder, dirname($composer_bin, 2));
    $this->assertSame($composer_bin, $finder->find('composer'));

    // If the permissions change will fail, a warning should be logged.
    $predicate = function (array $record) use ($composer_bin): bool {
      return (
        $record['message'] === 'Composer was found at %path, but could not be made read-only.' &&
        $record['context']['%path'] === $composer_bin
      );
    };
    $this->assertSame(!$chmod_result, $logger->hasRecordThatPasses($predicate));

    // If the executable disappears, or Composer isn't locally installed, the
    // decorated executable finder should be called.
    unlink($composer_bin);
    $this->assertSame('the real Composer', $finder->find('composer'));

    $reflector->setValue($finder, FALSE);
    $this->assertSame('the real Composer', $finder->find('composer'));
  }

}
