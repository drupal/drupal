<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Component\Datetime\Time;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleUninstallValidatorException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Drupal\package_manager\Event\PostApplyEvent;
use Drupal\package_manager\Event\PostCreateEvent;
use Drupal\package_manager\Event\PostRequireEvent;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\Event\StageEvent;
use Drupal\package_manager\Exception\ApplyFailedException;
use Drupal\package_manager\Exception\StageEventException;
use Drupal\package_manager\Exception\StageException;
use Drupal\package_manager\Exception\StageFailureMarkerException;
use Drupal\package_manager\FailureMarker;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\Validator\WritableFileSystemValidator;
use Drupal\package_manager_bypass\LoggingBeginner;
use Drupal\package_manager_bypass\LoggingCommitter;
use Drupal\package_manager_bypass\NoOpStager;
use Drupal\package_manager_test_validation\EventSubscriber\TestSubscriber;
use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Core\CommitterInterface;
use PhpTuf\ComposerStager\API\Core\StagerInterface;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use Psr\Log\LogLevel;
use ColinODell\PsrTestLogger\TestLogger;

/**
 * @coversDefaultClass \Drupal\package_manager\StageBase
 * @covers \Drupal\package_manager\PackageManagerUninstallValidator
 * @group package_manager
 * @internal
 */
class StageBaseTest extends PackageManagerKernelTestBase {

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
   * Data provider for testLoggedOnError().
   *
   * @return string[][]
   *   The test cases.
   */
  public static function providerLoggedOnError(): array {
    return [
      [PreCreateEvent::class],
      [PostCreateEvent::class],
      [PreRequireEvent::class],
      [PostRequireEvent::class],
      [PreApplyEvent::class],
      [PostApplyEvent::class],
    ];
  }

  /**
   * @covers \Drupal\package_manager\StageBase::dispatch
   *
   * @dataProvider providerLoggedOnError
   *
   * @param string $event_class
   *   The event class to throw an exception on.
   */
  public function testLoggedOnError(string $event_class): void {
    $exception = new \Exception("This should be logged!");
    TestSubscriber::setException($exception, $event_class);

    $stage = $this->createStage();
    $logger = new TestLogger();
    $stage->setLogger($logger);

    try {
      $stage->create();
      $stage->require(['drupal/core:9.8.1']);
      $stage->apply();
      $stage->postApply();
      $this->fail('Expected an exception to be thrown, but none was.');
    }
    catch (StageEventException $e) {
      $this->assertInstanceOf($event_class, $e->event);

      $predicate = function (array $record) use ($e): bool {
        $context = $record['context'];
        return $context['@message'] === $e->getMessage() && str_contains($context['@backtrace_string'], 'testLoggedOnError');
      };
      $this->assertTrue($logger->hasRecordThatPasses($predicate, LogLevel::ERROR));
    }
  }

  /**
   * @covers ::getMetadata
   * @covers ::setMetadata
   */
  public function testMetadata(): void {
    $stage = $this->createStage();
    $stage->create();
    $this->assertNull($stage->getMetadata('new_key'));
    $stage->setMetadata('new_key', 'value');
    $this->assertSame('value', $stage->getMetadata('new_key'));
    $stage->destroy();

    // Ensure that metadata associated with the previous stage was deleted.
    $stage = $this->createStage();
    $stage->create();
    $this->assertNull($stage->getMetadata('new_key'));
    $stage->destroy();

    // Ensure metadata cannot be accessed or set unless the stage has been
    // claimed.
    $stage = $this->createStage();
    try {
      $stage->getMetadata('new_key');
      $this->fail('Expected an ownership exception, but none was thrown.');
    }
    catch (\LogicException $e) {
      $this->assertSame('Stage must be claimed before performing any operations on it.', $e->getMessage());
    }

    try {
      $stage->setMetadata('new_key', 'value');
      $this->fail('Expected an ownership exception, but none was thrown.');
    }
    catch (\LogicException $e) {
      $this->assertSame('Stage must be claimed before performing any operations on it.', $e->getMessage());
    }
  }

  /**
   * @covers ::getStageDirectory
   */
  public function testGetStageDirectory(): void {
    // In this test, we're working with paths that (probably) don't exist in
    // the file system at all, so we don't want to validate that the file system
    // is writable when creating stages.
    $validator = $this->container->get(WritableFileSystemValidator::class);
    $this->container->get('event_dispatcher')->removeSubscriber($validator);

    /** @var \Drupal\package_manager_bypass\MockPathLocator $path_locator */
    $path_locator = $this->container->get(PathLocator::class);

    $stage = $this->createStage();
    $id = $stage->create();
    $stage_dir = $stage->getStageDirectory();
    $this->assertStringStartsWith($path_locator->getStagingRoot() . '/', $stage_dir);
    $this->assertStringEndsWith("/$id", $stage_dir);
    // If the stage root directory is changed, the existing stage shouldn't be
    // affected...
    $active_dir = $path_locator->getProjectRoot();
    $new_staging_root = $this->testProjectRoot . DIRECTORY_SEPARATOR . 'junk';
    if (!is_dir($new_staging_root)) {
      mkdir($new_staging_root);
    }
    $path_locator->setPaths($active_dir, "$active_dir/vendor", '', $new_staging_root);
    $this->assertSame($stage_dir, $stage->getStageDirectory());
    $stage->destroy();
    // ...but a new stage should be.
    $stage = $this->createStage();
    $another_id = $stage->create();
    $this->assertNotSame($id, $another_id);
    $stage_dir = $stage->getStageDirectory();
    $this->assertStringStartsWith(realpath($new_staging_root), $stage_dir);
    $this->assertStringEndsWith("/$another_id", $stage_dir);
  }

  /**
   * @covers ::getStageDirectory
   */
  public function testUncreatedGetStageDirectory(): void {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Drupal\package_manager\StageBase::getStageDirectory() cannot be called because the stage has not been created or claimed.');
    $this->createStage()->getStageDirectory();
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
    $listener = function (StageEvent $event) use ($force, $time_offset): void {
      // Simulate that a certain amount of time has passed since we started
      // applying staged changes. After a point, it should be possible to
      // destroy the stage even if it hasn't finished.
      TestTime::$offset = $time_offset;

      // No real-life event subscriber should try to destroy the stage while
      // handling another event. The only reason we're doing it here is to
      // simulate an attempt to destroy the stage while it's being applied, for
      // testing purposes.
      $event->stage->destroy($force);
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
      $this->expectException(StageException::class);
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
   * Test uninstalling any module while the staged changes are being applied.
   */
  public function testUninstallModuleDuringApply(): void {
    $listener = function (PreApplyEvent $event): void {
      $this->assertTrue($event->stage->isApplying());

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

  /**
   * Tests that Composer Stager is invoked with a long timeout.
   */
  public function testTimeouts(): void {
    $stage = $this->createStage();
    $stage->create(420);
    $stage->require(['ext-json:*']);
    $stage->apply();

    $timeouts = [
      // The beginner was given an explicit timeout.
      BeginnerInterface::class => 420,
      // The stager should be called with a timeout of 300 seconds, which is
      // longer than Composer Stager's default timeout of 120 seconds.
      StagerInterface::class => 300,
      // The committer should have been called with an even longer timeout,
      // since it's the most failure-sensitive operation.
      CommitterInterface::class => 600,
    ];
    foreach ($timeouts as $service_id => $expected_timeout) {
      $invocations = $this->container->get($service_id)->getInvocationArguments();

      // The services should have been called with the expected timeouts.
      $expected_count = 1;
      if ($service_id === StagerInterface::class) {
        // Stage::require() calls Stager::stage() twice, once to change the
        // version constraints in composer.json, and again to actually update
        // the installed dependencies.
        $expected_count = 2;
      }
      $this->assertCount($expected_count, $invocations);
      $this->assertSame($expected_timeout, end($invocations[0]));
    }
  }

  /**
   * Data provider for testCommitException().
   *
   * @return \string[][]
   *   The test cases.
   */
  public static function providerCommitException(): array {
    return [
      'RuntimeException to ApplyFailedException' => [
        'RuntimeException',
        ApplyFailedException::class,
      ],
      'InvalidArgumentException' => [
        InvalidArgumentException::class,
        StageException::class,
      ],
      'PreconditionException' => [
        PreconditionException::class,
        StageException::class,
      ],
      'Exception' => [
        'Exception',
        ApplyFailedException::class,
      ],
    ];
  }

  /**
   * Tests exception handling during calls to Composer Stager commit.
   *
   * @param string $thrown_class
   *   The throwable class that should be thrown by Composer Stager.
   * @param string $expected_class
   *   The expected exception class, if different from $thrown_class.
   *
   * @dataProvider providerCommitException
   */
  public function testCommitException(string $thrown_class, string $expected_class): void {
    $stage = $this->createStage();
    $stage->create();
    $stage->require(['drupal/core:9.8.1']);

    $throwable_arguments = [
      'A very bad thing happened',
      123,
    ];
    // Composer Stager's exception messages are usually translatable, so they
    // need to be wrapped by a TranslatableMessage object.
    if (is_subclass_of($thrown_class, ExceptionInterface::class)) {
      $throwable_arguments[0] = $this->createComposeStagerMessage($throwable_arguments[0]);
    }
    // PreconditionException requires a preconditions object.
    if ($thrown_class === PreconditionException::class) {
      array_unshift($throwable_arguments, $this->createMock(PreconditionInterface::class));
    }
    LoggingCommitter::setException($thrown_class, ...$throwable_arguments);

    try {
      $stage->apply();
      $this->fail('Expected an exception.');
    }
    catch (\Throwable $exception) {
      $this->assertInstanceOf($expected_class, $exception);
      $this->assertSame(123, $exception->getCode());

      // This needs to be done because we always use the message from
      // \Drupal\package_manager\Stage::getFailureMarkerMessage() when throwing
      // ApplyFailedException.
      if ($expected_class == ApplyFailedException::class) {
        $this->assertMatchesRegularExpression("/^Staged changes failed to apply, and the site is in an indeterminate state. It is strongly recommended to restore the code and database from a backup. Caused by $thrown_class, with this message: A very bad thing happened\nBacktrace:\n#0 .*/", $exception->getMessage());
      }
      else {
        $this->assertSame('A very bad thing happened', $exception->getMessage());
      }

      $failure_marker = $this->container->get(FailureMarker::class);
      if ($exception instanceof ApplyFailedException) {
        $this->assertFileExists($failure_marker->getPath());
        $this->assertFalse($stage->isApplying());
      }
      else {
        $failure_marker->assertNotExists();
      }
    }
  }

  /**
   * Tests that if a stage fails to apply, another stage cannot be created.
   */
  public function testFailureMarkerPreventsCreate(): void {
    $stage = $this->createStage();
    $stage->create();
    $stage->require(['ext-json:*']);

    // Make the committer throw an exception, which should cause the failure
    // marker to be present.
    $thrown_message = 'Thrown by the committer.';
    LoggingCommitter::setException(\Exception::class, $thrown_message);
    try {
      $stage->apply();
      $this->fail('Expected an exception.');
    }
    catch (ApplyFailedException $e) {
      $this->assertStringContainsString($thrown_message, $e->getMessage());
      $this->assertFalse($stage->isApplying());
    }
    $stage->destroy();

    // Even through the previous stage was destroyed, we cannot create a new one
    // because the failure marker is still there.
    $stage = $this->createStage();
    try {
      $stage->create();
      $this->fail('Expected an exception.');
    }
    catch (StageFailureMarkerException $e) {
      $this->assertMatchesRegularExpression('/^Staged changes failed to apply, and the site is in an indeterminate state. It is strongly recommended to restore the code and database from a backup. Caused by Exception, with this message: ' . $thrown_message . "\nBacktrace:\n#0 .*/", $e->getMessage());
      $this->assertFalse($stage->isApplying());
    }

    // If the failure marker is cleared, we should be able to create the stage
    // without issue.
    $this->container->get(FailureMarker::class)->clear();
    $stage->create();
  }

  /**
   * Tests that the failure marker file doesn't exist if apply succeeds.
   *
   * @see ::testCommitException
   */
  public function testNoFailureFileOnSuccess(): void {
    $stage = $this->createStage();
    $stage->create();
    $stage->require(['ext-json:*']);
    $stage->apply();

    $this->container->get(FailureMarker::class)
      ->assertNotExists();
  }

  /**
   * Data provider for testStoreDestroyInfo().
   *
   * @return \string[][]
   *   The test cases.
   */
  public static function providerStoreDestroyInfo(): array {
    return [
      'Changes applied' => [
        FALSE,
        TRUE,
        NULL,
        'This operation has already been applied.',
      ],
      'Changes not applied and forced' => [
        TRUE,
        FALSE,
        NULL,
        'This operation was canceled by another user.',
      ],
      'Changes not applied and not forced' => [
        FALSE,
        FALSE,
        NULL,
        'This operation was already canceled.',
      ],
      'Changes applied, with a custom exception message.' => [
        FALSE,
        TRUE,
        t('Stage destroyed with a custom message.'),
        'Stage destroyed with a custom message.',
      ],
      'Changes not applied and forced, with a custom exception message.' => [
        TRUE,
        FALSE,
        t('Stage destroyed with a custom message.'),
        'Stage destroyed with a custom message.',
      ],
      'Changes not applied and not forced, with a custom exception message.' => [
        FALSE,
        FALSE,
        t('Stage destroyed with a custom message.'),
        'Stage destroyed with a custom message.',
      ],
    ];
  }

  /**
   * Tests exceptions thrown because of previously destroyed stage.
   *
   * @param bool $force
   *   Whether the stage was forcefully destroyed.
   * @param bool $changes_applied
   *   Whether the changes are applied.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $message
   *   A message about why the stage was destroyed or null.
   * @param string $expected_exception_message
   *   The expected exception message string.
   *
   * @dataProvider providerStoreDestroyInfo
   */
  public function testStoreDestroyInfo(bool $force, bool $changes_applied, ?TranslatableMarkup $message, string $expected_exception_message): void {
    $stage = $this->createStage();
    $stage_id = $stage->create();
    $stage->require(['drupal/core:9.8.1']);
    $tempstore = $this->container->get('tempstore.shared');
    // Simulate whether ::apply() has run or not.
    // @see \Drupal\package_manager\Stage::TEMPSTORE_CHANGES_APPLIED
    $tempstore->get('package_manager_stage')->set('changes_applied', $changes_applied);
    $stage->destroy($force, $message);

    // Prove the first stage was destroyed: a second stage can be created
    // without an exception being thrown.
    $stage2 = $this->createStage();
    $stage2->create();

    // Claiming the first stage always fails in this test because it was
    // destroyed, but the exception message depends on why it was destroyed.
    $this->expectException(StageException::class);
    $this->expectExceptionMessage($expected_exception_message);
    $stage->claim($stage_id);
  }

  /**
   * Tests exception message once temp store message has expired.
   */
  public function testTempStoreMessageExpired(): void {
    $stage = $this->createStage();
    $stage_id = $stage->create();
    $stage->require(['drupal/core:9.8.1']);
    $stage->destroy(TRUE, t('Force destroy stage.'));

    // Delete the tempstore message stored for the previously destroyed stage.
    $tempstore = $this->container->get('tempstore.shared');
    // @see \Drupal\package_manager\Stage::TEMPSTORE_DESTROYED_STAGES_INFO_PREFIX
    $tempstore->get('package_manager_stage')->delete('TEMPSTORE_DESTROYED_STAGES_INFO' . $stage_id);

    // Claiming the stage will fail, but we won't get the message we set in
    // \Drupal\package_manager\Stage::storeDestroyInfo() as we are deleting it
    // above.
    $this->expectException(StageException::class);
    $this->expectExceptionMessage('Cannot claim the stage because no stage has been created.');
    $stage->claim($stage_id);
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
   * Data provider for ::testFailureDuringComposerStagerOperations().
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerFailureDuringComposerStagerOperations(): array {
    return [
      [LoggingBeginner::class],
      [NoOpStager::class],
      [LoggingCommitter::class],
    ];
  }

  /**
   * Tests when Composer Stager throws an exception during an operation.
   *
   * @param class-string $throwing_class
   *   The fully qualified name of the Composer Stager class that should throw
   *   an exception. It is expected to have a static ::setException() method,
   *   provided by \Drupal\package_manager_bypass\ComposerStagerExceptionTrait.
   *
   * @dataProvider providerFailureDuringComposerStagerOperations
   */
  public function testFailureDuringComposerStagerOperations(string $throwing_class): void {
    $exception_message = "$throwing_class is angry!";
    $throwing_class::setException(\Exception::class, $exception_message, 1024);

    $expected_message = preg_quote($exception_message);
    if ($throwing_class === LoggingCommitter::class) {
      $expected_message = "/^Staged changes failed to apply, and the site is in an indeterminate state. It is strongly recommended to restore the code and database from a backup. Caused by Exception, with this message: $expected_message\nBacktrace:\n#0 .*/";
    }
    else {
      $expected_message = "/^$expected_message$/";
    }

    $stage = $this->createStage();
    try {
      $stage->create();
      $stage->require(['ext-json:*']);
      $stage->apply();
      $this->fail('Expected an exception to be thrown, but it was not.');
    }
    catch (StageException $e) {
      $this->assertMatchesRegularExpression($expected_message, $e->getMessage());
      $this->assertSame(1024, $e->getCode());
      $this->assertInstanceOf(\Exception::class, $e->getPrevious());
    }
  }

  /**
   * Tests that paths to exclude are collected before create and apply.
   */
  public function testCollectPathsToExclude(): void {
    $this->addEventTestListener(function (CollectPathsToExcludeEvent $event): void {
      $event->add('exclude/me');
    }, CollectPathsToExcludeEvent::class);

    // On pre-create and pre-apply, ensure that the excluded path is known to
    // the event.
    $asserted = FALSE;
    $assert_excluded = function (object $event) use (&$asserted): void {
      $this->assertContains('exclude/me', $event->excludedPaths->getAll());
      // Use this to confirm that this listener was actually called.
      $asserted = TRUE;
    };
    $this->addEventTestListener($assert_excluded, PreCreateEvent::class);
    $this->addEventTestListener($assert_excluded);

    $stage = $this->createStage();
    $stage->create();
    $this->assertTrue($asserted);
    $asserted = FALSE;
    $stage->require(['ext-json:*']);
    $stage->apply();
    $this->assertTrue($asserted);
  }

  /**
   * Tests that the failure marker file is excluded using a relative path.
   */
  public function testFailureMarkerFileExcluded(): void {
    $this->assertResults([]);
    /** @var \Drupal\package_manager_bypass\LoggingCommitter $committer */
    $committer = $this->container->get(CommitterInterface::class);
    $committer_args = $committer->getInvocationArguments();
    $this->assertCount(1, $committer_args);
    $this->assertContains('PACKAGE_MANAGER_FAILURE.yml', $committer_args[0][2]);
  }

  /**
   * Tests that if a stage fails to get paths to exclude, throws a stage exception.
   */
  public function testFailureCollectPathsToExclude(): void {
    $project_root = $this->container->get(PathLocator::class)->getProjectRoot();
    unlink($project_root . '/composer.json');
    $this->expectException(StageException::class);
    $this->expectExceptionMessage("composer.json not found.");
    $this->createStage()->create();
  }

  /**
   * Tests that if apply fails to get paths to exclude, throws a stage exception.
   */
  public function testFailureCollectPathsToExcludeOnApply(): void {
    $stage = $this->createStage();
    $stage->create();
    $stage->require(['drupal/random']);
    $this->expectException(StageException::class);
    $this->expectExceptionMessage("composer.json not found.");
    unlink($stage->getStageDirectory() . '/composer.json');
    $stage->apply();
  }

  /**
   * @covers ::stageDirectoryExists
   */
  public function testStageDirectoryExists(): void {
    // Ensure that stageDirectoryExists() returns an accurate result during
    // pre-create.
    $listener = function (StageEvent $event): void {
      $stage = $event->stage;
      // The directory should not exist yet, because we are still in pre-create.
      $this->assertDirectoryDoesNotExist($stage->getStageDirectory());
      $this->assertFalse($stage->stageDirectoryExists());
    };
    $this->addEventTestListener($listener, PreCreateEvent::class);

    $stage = $this->createStage();
    $this->assertFalse($stage->stageDirectoryExists());
    $stage->create();
    $this->assertTrue($stage->stageDirectoryExists());
  }

  /**
   * Tests that destroyed stage directories are actually deleted during cron.
   *
   * @covers ::destroy
   * @covers \Drupal\package_manager\Plugin\QueueWorker\Cleaner
   */
  public function testStageDirectoryDeletedDuringCron(): void {
    $stage = $this->createStage();
    $stage->create();
    $dir = $stage->getStageDirectory();
    $this->assertDirectoryExists($dir);
    $stage->destroy();
    // The stage directory should still exist, but the stage should be
    // available.
    $this->assertTrue($stage->isAvailable());
    $this->assertDirectoryExists($dir);

    $this->container->get('cron')->run();
    $this->assertDirectoryDoesNotExist($dir);
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
