<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Drupal\package_manager\Exception\FailureMarkerExistsException;
use Drupal\package_manager\FailureMarker;
use Drupal\package_manager\PathLocator;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;

/**
 * @coversDefaultClass \Drupal\package_manager\FailureMarker
 * @group package_manager
 * @internal
 */
class FailureMarkerTest extends PackageManagerKernelTestBase {

  use StringTranslationTrait;

  /**
   * @covers ::getMessage
   * @testWith [true]
   *   [false]
   */
  public function testGetMessageWithoutThrowable(bool $include_backtrace): void {
    $failure_marker = $this->container->get(FailureMarker::class);
    $failure_marker->write($this->createStage(), $this->t('Disastrous catastrophe!'));

    $this->assertMatchesRegularExpression('/^Disastrous catastrophe!$/', $failure_marker->getMessage($include_backtrace));
  }

  /**
   * @covers ::getMessage
   * @testWith [true]
   *   [false]
   */
  public function testGetMessageWithThrowable(bool $include_backtrace): void {
    $failure_marker = $this->container->get(FailureMarker::class);
    $failure_marker->write($this->createStage(), $this->t('Disastrous catastrophe!'), new \Exception('Witchcraft!'));

    $expected_pattern = $include_backtrace
      ? <<<REGEXP
/^Disastrous catastrophe! Caused by Exception, with this message: Witchcraft!
Backtrace:
#0 .*FailureMarkerTest->testGetMessageWithThrowable\(true\)
#1 .*
#2 .*
#3 .*/
REGEXP
      : '/^Disastrous catastrophe! Caused by Exception, with this message: Witchcraft!$/';
    $this->assertMatchesRegularExpression(
      $expected_pattern,
      $failure_marker->getMessage($include_backtrace)
    );
  }

  /**
   * Tests that an exception is thrown if the marker file contains invalid YAML.
   *
   * @covers ::assertNotExists
   */
  public function testExceptionForInvalidYaml(): void {
    $failure_marker = $this->container->get(FailureMarker::class);
    // Write the failure marker with invalid YAML.
    file_put_contents($failure_marker->getPath(), 'message : something message : something1');

    $this->expectException(FailureMarkerExistsException::class);
    $this->expectExceptionMessage('Failure marker file exists but cannot be decoded.');
    $failure_marker->assertNotExists();
  }

  /**
   * Tests that the failure marker can contain an exception message.
   *
   * @covers ::assertNotExists
   */
  public function testAssertNotExists(): void {
    $failure_marker = $this->container->get(FailureMarker::class);
    $failure_marker->write($this->createStage(), $this->t('Something wicked occurred here.'), new \Exception('Witchcraft!'));

    $this->expectException(FailureMarkerExistsException::class);
    $this->expectExceptionMessageMatches('/^Something wicked occurred here. Caused by Exception, with this message: Witchcraft!\nBacktrace:\n#0 .*/');
    $failure_marker->assertNotExists();
  }

  /**
   * @covers ::getSubscribedEvents
   * @covers ::excludeMarkerFile
   */
  public function testMarkerFileIsExcluded(): void {
    $event = new CollectPathsToExcludeEvent(
      $this->createStage(),
      $this->container->get(PathLocator::class),
      $this->container->get(PathFactoryInterface::class),
    );
    $this->container->get('event_dispatcher')->dispatch($event);
    $this->assertContains('PACKAGE_MANAGER_FAILURE.yml', $event->getAll());
  }

}
