<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\StageEvent;
use Drupal\package_manager\Exception\ApplyFailedException;
use Drupal\package_manager\Exception\StageException;
use Drupal\package_manager\Exception\StageFailureMarkerException;
use Drupal\package_manager\FailureMarker;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\Validator\WritableFileSystemValidator;
use Drupal\package_manager_bypass\LoggingBeginner;
use Drupal\package_manager_bypass\LoggingCommitter;
use Drupal\package_manager_bypass\NoOpStager;
use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Core\CommitterInterface;
use PhpTuf\ComposerStager\API\Core\StagerInterface;

/**
 * @coversDefaultClass \Drupal\package_manager\StageBase
 * @group package_manager
 * @group #slow
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

    // Since this test adds arbitrary event listeners that aren't services, we
    // need to ensure they will persist even if the container is rebuilt when
    // staged changes are applied.
    $container->getDefinition('event_dispatcher')->addTag('persist');
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
