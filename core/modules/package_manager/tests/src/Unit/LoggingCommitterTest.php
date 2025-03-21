<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\package_manager\FileProcessOutputCallback;
use Drupal\package_manager\LoggingCommitter;
use Drupal\package_manager\ProcessOutputCallback;
use Drupal\Tests\UnitTestCase;
use PhpTuf\ComposerStager\API\Core\CommitterInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;

/**
 * @covers \Drupal\package_manager\LoggingCommitter
 * @group package_manager
 */
class LoggingCommitterTest extends UnitTestCase {

  /**
   * Tests the output of LoggingCommitter().
   */
  public function testDecoratedCommitterIsCalled(): void {
    $decorated = $this->createMock(CommitterInterface::class);

    $stagingDir = $this->createMock(PathInterface::class);
    $stagingDir->expects($this->any())
      ->method('absolute')
      ->willReturn('staging-dir');
    $activeDir = $this->createMock(PathInterface::class);
    $activeDir->expects($this->any())
      ->method('absolute')
      ->willReturn('active-dir');

    $decorated->expects($this->once())
      ->method('commit')
      ->with(
        $stagingDir,
        $activeDir,
        NULL,
        $this->isInstanceOf(FileProcessOutputCallback::class),
      );

    $config_factory = $this->getConfigFactoryStub([
      'package_manager.settings' => ['log' => 'php://memory'],
    ]);
    $time = $this->createMock(TimeInterface::class);
    $time->expects($this->atLeast(2))
      ->method('getCurrentMicroTime')
      ->willReturnOnConsecutiveCalls(1, 2.5);

    $callback = new ProcessOutputCallback();

    (new LoggingCommitter($decorated, $config_factory, $time))
      ->commit($stagingDir, $activeDir, callback: $callback);

    $this->assertSame([
      "### Committing changes from staging-dir to active-dir\n",
      "### Finished in 1.500 seconds\n",
    ], $callback->getOutput());
  }

}
