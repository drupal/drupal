<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Component\Datetime\Time;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleUninstallValidatorException;
use Drupal\package_manager\Event\PostApplyEvent;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\SandboxEvent;
use Drupal\package_manager\Exception\SandboxException;
use Drupal\package_manager_bypass\LoggingCommitter;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use Psr\Log\LogLevel;
use ColinODell\PsrTestLogger\TestLogger;

/**
 * @coversDefaultClass \Drupal\package_manager\SandboxManagerBase
 * @covers \Drupal\package_manager\PackageManagerUninstallValidator
 * @group package_manager
 * @group #slow
 * @internal
 */
class StageConflictTest extends PackageManagerKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['package_manager_test_validation'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    $container->getDefinition('datetime.time')
      ->setClass(TestTime::class);

    // Since this test adds arbitrary event listeners that aren't services, we
    // need to ensure they will persist even if the container is rebuilt when
    // staged changes are applied.
    $container->getDefinition('event_dispatcher')->addTag('persist');
  }

  /**
   * Data provider for testDestroyDuringApply().
   *
   * @return mixed[][]
   *   The test cases.
   */
  public static function providerDestroyDuringApply(): array {
    $error_message_while_being_applied = 'Cannot destroy the stage directory while it is being applied to the active directory.';
    return [
      'force destroy on pre-apply, fresh' => [
        PreApplyEvent::class,
        TRUE,
        1,
        $error_message_while_being_applied,
      ],
      'destroy on pre-apply, fresh' => [
        PreApplyEvent::class,
        FALSE,
        1,
        $error_message_while_being_applied,
      ],
      'force destroy on pre-apply, stale' => [
        PreApplyEvent::class,
        TRUE,
        7200,
        'Stage directory does not exist',
      ],
      'destroy on pre-apply, stale' => [
        PreApplyEvent::class,
        FALSE,
        7200,
        'Stage directory does not exist',
      ],
      'force destroy on post-apply, fresh' => [
        PostApplyEvent::class,
        TRUE,
        1,
        $error_message_while_being_applied,
      ],
      'destroy on post-apply, fresh' => [
        PostApplyEvent::class,
        FALSE,
        1,
        $error_message_while_being_applied,
      ],
      'force destroy on post-apply, stale' => [
        PostApplyEvent::class,
        TRUE,
        7200,
        NULL,
      ],
      'destroy on post-apply, stale' => [
        PostApplyEvent::class,
        FALSE,
        7200,
        NULL,
      ],
    ];
  }

  /**
   * Tests destroying a stage while applying it.
   *
   * @param string $event_class
   *   The event class for which to attempt to destroy the stage.
   * @param bool $force
   *   Whether the stage should be force destroyed.
   * @param int $time_offset
   *   How many simulated seconds should have elapsed between the PreApplyEvent
   *   being dispatched and the attempt to destroy the stage.
   * @param string|null $expected_exception_message
   *   The expected exception message string if an exception is expected, or
   *   NULL if no exception message was expected.
   *
   * @dataProvider providerDestroyDuringApply
   */
  public function testDestroyDuringApply(string $event_class, bool $force, int $time_offset, ?string $expected_exception_message): void {
    $listener = function (SandboxEvent $event) use ($force, $time_offset): void {
      // Simulate that a certain amount of time has passed since we started
      // applying staged changes. After a point, it should be possible to
      // destroy the stage even if it hasn't finished.
      TestTime::$offset = $time_offset;

      // No real-life event subscriber should try to destroy the stage while
      // handling another event. The only reason we're doing it here is to
      // simulate an attempt to destroy the stage while it's being applied, for
      // testing purposes.
      $event->sandboxManager->destroy($force);
      LoggingCommitter::setException(
        PreconditionException::class,
        $this->createMock(PreconditionInterface::class),
        $this->createComposeStagerMessage('Stage directory does not exist'),
      );
    };
    $this->addEventTestListener($listener, $event_class, 0);

    $stage = $this->createStage();
    $stage->create();
    $stage->require(['ext-json:*']);
    if ($expected_exception_message) {
      $this->expectException(SandboxException::class);
      $this->expectExceptionMessage($expected_exception_message);
    }
    $stage->apply();

    // If the stage was successfully destroyed by the event handler (i.e., the
    // stage has been applying for too long and is therefore considered stale),
    // the postApply() method should fail because the stage is not claimed.
    if ($stage->isAvailable()) {
      $this->expectException(\LogicException::class);
      $this->expectExceptionMessage('Stage must be claimed before performing any operations on it.');
    }
    $stage->postApply();
  }

  /**
   * Tests running apply and post-apply in the same request.
   */
  public function testApplyAndPostApplyInSameRequest(): void {
    $stage = $this->createStage();

    $logger = new TestLogger();
    $stage->setLogger($logger);
    $warning_message = 'Post-apply tasks are running in the same request during which staged changes were applied to the active code base. This can result in unpredictable behavior.';

    // Run apply and post-apply in the same request (i.e., the same request
    // time), and ensure the warning is logged.
    $stage->create();
    $stage->require(['drupal/core:9.8.1']);
    $stage->apply();
    $stage->postApply();
    $this->assertTrue($logger->hasRecord($warning_message, LogLevel::WARNING));
    $stage->destroy();

    $logger->reset();
    $stage->create();
    $stage->require(['drupal/core:9.8.2']);
    $stage->apply();
    // Simulate post-apply taking place in another request by simulating a
    // request time 30 seconds after apply started.
    TestTime::$offset = 30;
    $stage->postApply();
    $this->assertFalse($logger->hasRecord($warning_message, LogLevel::WARNING));
  }

  /**
   * Test uninstalling any module while the staged changes are being applied.
   */
  public function testUninstallModuleDuringApply(): void {
    $listener = function (PreApplyEvent $event): void {
      $this->assertTrue($event->sandboxManager->isApplying());

      // Trying to uninstall any module while the stage is being applied should
      // result in a module uninstall validation error.
      try {
        $this->container->get('module_installer')
          ->uninstall(['package_manager_bypass']);
        $this->fail('Expected an exception to be thrown while uninstalling a module.');
      }
      catch (ModuleUninstallValidatorException $e) {
        $this->assertStringContainsString('Modules cannot be uninstalled while Package Manager is applying staged changes to the active code base.', $e->getMessage());
      }
    };
    $this->addEventTestListener($listener);

    $stage = $this->createStage();
    $stage->create();
    $stage->require(['ext-json:*']);
    $stage->apply();
  }

}

/**
 * A test-only implementation of the time service.
 */
class TestTime extends Time {

  /**
   * An offset to add to the request time.
   *
   * @var int
   */
  public static $offset = 0;

  /**
   * {@inheritdoc}
   */
  public function getRequestTime() {
    return parent::getRequestTime() + static::$offset;
  }

}
