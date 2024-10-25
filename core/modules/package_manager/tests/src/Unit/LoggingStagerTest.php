<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Unit;

use Drupal\package_manager\FileProcessOutputCallback;
use Drupal\package_manager\LoggingStager;
use Drupal\Tests\UnitTestCase;
use PhpTuf\ComposerStager\API\Core\StagerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Value\OutputTypeEnum;

/**
 * @covers \Drupal\package_manager\LoggingStager
 * @group package_manager
 */
class LoggingStagerTest extends UnitTestCase {

  public function testDecoratedStagerIsCalled(): void {
    $decorated = $this->createMock(StagerInterface::class);

    $activeDir = $this->createMock(PathInterface::class);
    $stagingDir = $this->createMock(PathInterface::class);
    $stagingDir->expects($this->any())
      ->method('absolute')
      ->willReturn('staging-dir');

    $original_callback = $this->createMock(OutputCallbackInterface::class);
    $original_callback->expects($this->once())
      ->method('__invoke')
      ->with(OutputTypeEnum::OUT, "### Staging '--version' in staging-dir\n");

    $decorated->expects($this->once())
      ->method('stage')
      ->with(
        ['--version'],
        $activeDir,
        $stagingDir,
        $this->isInstanceOf(FileProcessOutputCallback::class),
      );

    $config_factory = $this->getConfigFactoryStub([
      'package_manager.settings' => ['log' => 'php://memory'],
    ]);
    $decorator = new LoggingStager($decorated, $config_factory);
    $decorator->stage(['--version'], $activeDir, $stagingDir, callback: $original_callback);
  }

}
