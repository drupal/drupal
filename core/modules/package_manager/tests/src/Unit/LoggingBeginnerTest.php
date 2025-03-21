<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\package_manager\FileProcessOutputCallback;
use Drupal\package_manager\LoggingBeginner;
use Drupal\package_manager\ProcessOutputCallback;
use Drupal\Tests\UnitTestCase;
use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;

/**
 * @covers \Drupal\package_manager\LoggingBeginner
 * @group package_manager
 */
class LoggingBeginnerTest extends UnitTestCase {

  /**
   * Tests the output of LoggingBeginner().
   */
  public function testDecoratedBeginnerIsCalled(): void {
    $decorated = $this->createMock(BeginnerInterface::class);

    $activeDir = $this->createMock(PathInterface::class);
    $stagingDir = $this->createMock(PathInterface::class);
    $stagingDir->expects($this->any())
      ->method('absolute')
      ->willReturn('staging-dir');

    $decorated->expects($this->once())
      ->method('begin')
      ->with(
        $activeDir,
        $stagingDir,
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

    (new LoggingBeginner($decorated, $config_factory, $time))
      ->begin($activeDir, $stagingDir, callback: $callback);

    $this->assertSame([
      "### Beginning in staging-dir\n",
      "### Finished in 1.500 seconds\n",
    ], $callback->getOutput());
  }

}
