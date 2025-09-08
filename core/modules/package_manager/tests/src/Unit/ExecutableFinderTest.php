<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Unit;

use ColinODell\PsrTestLogger\TestLogger;
use Drupal\Component\Serialization\Json;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Drupal\package_manager\ExecutableFinder;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests Package Manager's executable finder service.
 *
 * @internal
 */
#[Group('package_manager')]
#[CoversClass(ExecutableFinder::class)]
class ExecutableFinderTest extends UnitTestCase {

  /**
   * Tests that the executable finder tries to use a local copy of Composer.
   */
  #[TestWith([TRUE])]
  #[TestWith([FALSE])]
  public function testComposerInstalledInProject(bool $chmod_result): void {
    vfsStream::setup('root', NULL, [
      'composer-path' => [
        'bin' => [
          'composer' => 'A fake Composer executable.',
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
      $file_system->reveal(),
      $this->getConfigFactoryStub([
        'package_manager.settings' => [],
      ]),
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

    // If the executable disappears and Composer isn't locally installed, fall
    // back to a setting.
    unlink($composer_bin);
    $reflector->setValue($finder, FALSE);
    new Settings([
      'package_manager_composer_path' => 'Composer from settings',
    ]);
    $this->assertSame('Composer from settings', $finder->find('composer'));

    // If all else fails, the decorated executable finder should be called.
    new Settings([]);
    $this->assertSame('the real Composer', $finder->find('composer'));
  }

  /**
   * Tests that the executable finder falls back to looking in config for paths.
   */
  #[Group('legacy')]
  public function testLegacyExecutablePaths(): void {
    $exception = $this->prophesize(LogicException::class);

    $decorated = $this->prophesize(ExecutableFinderInterface::class);
    $decorated->find('composer')->willThrow($exception->reveal());
    $decorated->find('rsync')->willThrow($exception->reveal());

    $finder = new ExecutableFinder(
      $decorated->reveal(),
      $this->prophesize(FileSystemInterface::class)->reveal(),
      $this->getConfigFactoryStub([
        'package_manager.settings' => [
          'executables' => [
            'composer' => 'legacy-composer',
            'rsync' => 'legacy-rsync',
          ],
        ],
      ]),
    );
    // Simulate Composer not being locally installed, with no fallback setting.
    $reflector = new \ReflectionProperty($finder, 'composerPackagePath');
    $reflector->setValue($finder, FALSE);

    $this->expectDeprecation("Storing the path to Composer in configuration is deprecated in drupal:11.2.4 and not supported in drupal:12.0.0. Add composer/composer directly to your project's dependencies instead. See https://www.drupal.org/node/3540264");
    $finder->find('composer');

    $this->expectDeprecation("Storing the path to rsync in configuration is deprecated in drupal:11.2.4 and not supported in drupal:12.0.0. Move it to the <code>package_manager_rsync_path</code> setting instead. See https://www.drupal.org/node/3540264");
    $finder->find('rsync');
  }

}
