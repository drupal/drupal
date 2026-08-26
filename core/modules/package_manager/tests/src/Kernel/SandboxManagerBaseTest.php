<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\SandboxEvent;
use Drupal\package_manager\EventSubscriber\LongLivedSandboxSubscriber;
use Drupal\package_manager\Exception\ApplyFailedException;
use Drupal\package_manager\Exception\FailureMarkerExistsException;
use Drupal\package_manager\Exception\SandboxException;
use Drupal\package_manager\FailureMarker;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\SandboxManagerBase;
use Drupal\package_manager\Validator\WritableFileSystemValidator;
use Drupal\package_manager_bypass\LoggingBeginner;
use Drupal\package_manager_bypass\LoggingCommitter;
use Drupal\package_manager_bypass\NoOpStager;
use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Core\CommitterInterface;
use PhpTuf\ComposerStager\API\Core\StagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\package_manager\SandboxManagerBase.
 *
 * @internal
 */
#[CoversClass(SandboxManagerBase::class)]
#[Group('package_manager')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class SandboxManagerBaseTest extends PackageManagerKernelTestBase {

  use StringTranslationTrait;

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
   * Tests metadata.
   *
   * @legacy-covers ::getMetadata
   * @legacy-covers ::setMetadata
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
   * Tests get sandbox directory.
   */
  public function testGetSandboxDirectory(): void {
    // In this test, we're working with paths that (probably) don't exist in
    // the file system at all, so we don't want to validate that the file system
    // is writable when creating stages.
    $validator = $this->container->get(WritableFileSystemValidator::class);
    $this->container->get('event_dispatcher')->removeSubscriber($validator);

    /** @var \Drupal\package_manager_bypass\MockPathLocator $path_locator */
    $path_locator = $this->container->get(PathLocator::class);

    $stage = $this->createStage();
    $id = $stage->create();
    $stage_dir = $stage->getSandboxDirectory();
    $this->assertStringStartsWith($path_locator->getStagingRoot() . '/', $stage_dir);
    $this->assertStringEndsWith("/sandbox_directory", $stage_dir);
    // If the stage root directory is changed, the existing stage shouldn't be
    // affected...
    $active_dir = $path_locator->getProjectRoot();
    $new_staging_root = $this->testProjectRoot . DIRECTORY_SEPARATOR . 'junk';
    if (!is_dir($new_staging_root)) {
      mkdir($new_staging_root);
    }
    $path_locator->setPaths($active_dir, "$active_dir/vendor", '', $new_staging_root);
    $this->assertSame($stage_dir, $stage->getSandboxDirectory());
    $stage->destroy();
    // ...but a new stage should be.
    $stage = $this->createStage();
    $another_id = $stage->create();
    $this->assertNotSame($id, $another_id);
    $stage_dir = $stage->getSandboxDirectory();
    $this->assertStringStartsWith(realpath($new_staging_root), $stage_dir);
    $this->assertStringEndsWith("/sandbox_directory", $stage_dir);
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
    catch (FailureMarkerExistsException $e) {
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
   */
  #[DataProvider('providerStoreDestroyInfo')]
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
    $this->expectException(SandboxException::class);
    $this->expectExceptionMessageIs($expected_exception_message);
    $stage->claim($stage_id);
  }

  /**
   * Tests exception message once temp store message has expired.
   */
  public function testTempStoreMessageExpired(): void {
    $stage = $this->createStage();
    $stage_id = $stage->create();
    $stage->require(['drupal/core:9.8.1']);
    $stage->destroy(TRUE, $this->t('Force destroy stage.'));

    // Delete the tempstore message stored for the previously destroyed stage.
    $tempstore = $this->container->get('tempstore.shared');
    // @see \Drupal\package_manager\Stage::TEMPSTORE_DESTROYED_STAGES_INFO_PREFIX
    $tempstore->get('package_manager_stage')->delete('TEMPSTORE_DESTROYED_STAGES_INFO' . $stage_id);

    // Claiming the stage will fail, but we won't get the message we set in
    // \Drupal\package_manager\Stage::storeDestroyInfo() as we are deleting it
    // above.
    $this->expectException(SandboxException::class);
    $this->expectExceptionMessageIs('Cannot claim the stage because no stage has been created.');
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
   */
  #[DataProvider('providerFailureDuringComposerStagerOperations')]
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
    catch (SandboxException $e) {
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
    $this->expectException(SandboxException::class);
    $this->expectExceptionMessageIsOrContains("composer.json not found.");
    $this->createStage()->create();
  }

  /**
   * Tests that if apply fails to get paths to exclude, throws a stage exception.
   */
  public function testFailureCollectPathsToExcludeOnApply(): void {
    $stage = $this->createStage();
    $stage->create();
    $stage->require(['drupal/random']);
    $this->expectException(SandboxException::class);
    $this->expectExceptionMessageIsOrContains("composer.json not found.");
    unlink($stage->getSandboxDirectory() . '/composer.json');
    $stage->apply();
  }

  /**
   * Tests stage directory exists.
   *
   * @legacy-covers ::sandboxDirectoryExists
   */
  public function testStageDirectoryExists(): void {
    // Ensure that sandboxDirectoryExists() returns an accurate result during
    // pre-create.
    $listener = function (SandboxEvent $event): void {
      $stage = $event->sandboxManager;
      // The directory should not exist yet, because we are still in pre-create.
      $this->assertDirectoryDoesNotExist($stage->getSandboxDirectory());
      $this->assertFalse($stage->sandboxDirectoryExists());
    };
    $this->addEventTestListener($listener, PreCreateEvent::class);

    $stage = $this->createStage();
    $this->assertFalse($stage->sandboxDirectoryExists());
    $stage->create();
    $this->assertTrue($stage->sandboxDirectoryExists());

    // After destroy(), the directory still physically exists on disk (long-lived
    // sandbox), but sandboxDirectoryExists() should return FALSE because there
    // is no active lock.
    $dir = $stage->getSandboxDirectory();
    $stage->destroy();
    $this->assertDirectoryExists($dir);
    $this->assertTrue($stage->isAvailable());
    $this->assertFalse($stage->sandboxDirectoryExists());
  }

  /**
   * Tests that destroyed stage directories are actually deleted during cron.
   *
   * @legacy-covers ::destroy
   * @legacy-covers \Drupal\package_manager\Hook\CronHook
   */
  public function testStageDirectoryDeletedDuringCron(): void {
    $stage = $this->createStage();
    $stage->create();
    $dir = $stage->getSandboxDirectory();
    $this->assertDirectoryExists($dir);
    $stage->destroy();
    // The stage directory should still exist, but the stage should be
    // available.
    $this->assertTrue($stage->isAvailable());
    $this->assertDirectoryExists($dir);

    // The stage directory should still exist (by default) up to 14 days since
    // the last $stage->create() was run.
    $fresh_directory_age = time() - (13 * 24 * 60 * 60);
    $this->container->get('state')->set(LongLivedSandboxSubscriber::STATE_KEY, $fresh_directory_age);
    $this->container->get('cron')->run();
    $this->assertDirectoryExists($dir);
    $stale_directory_age = time() - (15 * 24 * 60 * 60);
    $this->container->get('state')->set(LongLivedSandboxSubscriber::STATE_KEY, $stale_directory_age);
    $this->container->get('cron')->run();
    $this->assertDirectoryDoesNotExist($dir);
  }

  /**
   * Tests that the sandbox directory persists after destroy and is reused.
   *
   * The long-lived sandbox directory should survive destroy() and be reused
   * across create/destroy cycles.
   *
   * @legacy-covers ::getSandboxDirectory
   * @legacy-covers ::destroy
   * @legacy-covers ::create
   */
  public function testSandboxDirectoryPersistsAndIsReused(): void {
    $stage = $this->createStage();
    $stage->create();
    $first_dir = $stage->getSandboxDirectory();
    $this->assertDirectoryExists($first_dir);

    // Place a marker file in the sandbox directory.
    file_put_contents($first_dir . '/marker.txt', 'test');
    $stage->destroy();

    // Directory and its contents should persist after destroy.
    $this->assertDirectoryExists($first_dir);
    $this->assertFileExists($first_dir . '/marker.txt');

    // A second create should succeed with the existing directory and use the
    // exact same path.
    $stage = $this->createStage();
    $stage->create();
    $this->assertSame($first_dir, $stage->getSandboxDirectory());
    $this->assertDirectoryExists($first_dir);
    $stage->destroy();
  }

  /**
   * Tests that getSandboxDirectory() works without a lock.
   *
   * The sandbox directory path should be deterministic and accessible without
   * creating or claiming the stage, since other components (CronHook,
   * LongLivedSandboxSubscriber) need to check the path for cleanup decisions.
   *
   * @legacy-covers ::getSandboxDirectory
   */
  public function testGetSandboxDirectoryWithoutLock(): void {
    $stage = $this->createStage();

    // getSandboxDirectory() should work without creating/claiming the stage.
    $dir = $stage->getSandboxDirectory();
    $this->assertStringEndsWith('/sandbox_directory', $dir);

    // The returned path should be consistent.
    $this->assertSame($dir, $stage->getSandboxDirectory());
  }

  /**
   * Tests that getStagingRoot() does not pollute tempstore without a lock.
   *
   * When getSandboxDirectory() is called without a lock (e.g. from CronHook),
   * it should NOT cache the staging root in the shared tempstore, since that
   * would pollute state visible to other stage operations.
   *
   * @legacy-covers ::getSandboxDirectory
   */
  public function testGetStagingRootDoesNotPolluteTempstore(): void {
    $stage = $this->createStage();
    $tempstore = $this->container->get('tempstore.shared')
      ->get('package_manager_stage');

    // Before any create, call getSandboxDirectory() which internally calls
    // getStagingRoot(). This should NOT write to tempstore.
    $stage->getSandboxDirectory();
    $this->assertNull($tempstore->get('staging_root'));

    // After creating the stage (which acquires a lock), the staging root
    // should be cached in tempstore.
    $stage->create();
    $this->assertNotNull($tempstore->get('staging_root'));
    $stage->destroy();
  }

  /**
   * Tests that LongLivedSandboxSubscriber tracks the create event time.
   *
   * @legacy-covers \Drupal\package_manager\EventSubscriber\LongLivedSandboxSubscriber
   */
  public function testLongLivedSandboxSubscriberTracksTime(): void {
    $state = $this->container->get('state');

    // No timestamp should exist before any stage is created.
    $this->assertNull($state->get(LongLivedSandboxSubscriber::STATE_KEY));

    $stage = $this->createStage();
    $stage->create();

    // After creating a stage, the timestamp should be set.
    /** @var int $timestamp */
    $timestamp = $state->get(LongLivedSandboxSubscriber::STATE_KEY);
    $this->assertNotNull($timestamp);
    $this->assertIsInt($timestamp);
    $this->assertGreaterThan(0, $timestamp);
    $stage->destroy();

    // The timestamp should persist after destroy, since it tracks the age
    // of the long-lived sandbox directory.
    $this->assertSame($timestamp, $state->get(LongLivedSandboxSubscriber::STATE_KEY));
  }

  /**
   * Tests that cron does not delete the sandbox when no create event occurred.
   *
   * If there's no recorded create event in state (e.g. state was cleared),
   * cron should not attempt to delete the sandbox directory even if it exists.
   *
   * @legacy-covers \Drupal\package_manager\Hook\CronHook
   */
  public function testCronDoesNotDeleteWithoutCreateEvent(): void {
    $stage = $this->createStage();
    $stage->create();
    $dir = $stage->getSandboxDirectory();
    $this->assertDirectoryExists($dir);
    $stage->destroy();

    // Clear the create event timestamp from state.
    $this->container->get('state')->delete(LongLivedSandboxSubscriber::STATE_KEY);

    // Cron should not delete the sandbox directory without a create event.
    $this->container->get('cron')->run();
    $this->assertDirectoryExists($dir);
  }

  /**
   * Tests that the sandbox directory is deleted on config change.
   *
   * When 'include_unknown_files_in_project_root' is changed to FALSE, the
   * sandbox directory should be deleted directly to ensure unknown files
   * from a previous sync are not retained.
   *
   * @legacy-covers \Drupal\package_manager\EventSubscriber\LongLivedSandboxSubscriber
   */
  public function testConfigChangeDeletesSandbox(): void {
    $stage = $this->createStage();
    $stage->create();
    $dir = $stage->getSandboxDirectory();
    $this->assertDirectoryExists($dir);
    $stage->destroy();
    $this->assertDirectoryExists($dir);

    // First enable the setting, then disable it to trigger the subscriber.
    $config = $this->config('package_manager.settings');
    $config->set('include_unknown_files_in_project_root', TRUE)->save();
    // Directory should still exist after enabling.
    $this->assertDirectoryExists($dir);

    // Disabling should delete the sandbox directory immediately.
    $config->set('include_unknown_files_in_project_root', FALSE)->save();
    $this->assertDirectoryDoesNotExist($dir);

    // The create event timestamp should also be cleared.
    $this->assertNull(
      $this->container->get('state')->get(LongLivedSandboxSubscriber::STATE_KEY)
    );
  }

  /**
   * Tests that config change does not trigger deletion while stage is active.
   *
   * If a stage is in use (locked), the sandbox should not be deleted even if
   * the config setting changes.
   *
   * @legacy-covers \Drupal\package_manager\EventSubscriber\LongLivedSandboxSubscriber
   */
  public function testConfigChangeDoesNotDeleteWhileStageIsActive(): void {
    $stage = $this->createStage();
    $stage->create();
    $dir = $stage->getSandboxDirectory();

    // Set up a listener that changes the config setting during pre-apply,
    // while the stage is active. This verifies the LongLivedSandboxSubscriber
    // respects the isAvailable check.
    $config = $this->config('package_manager.settings');
    $config->set('include_unknown_files_in_project_root', TRUE)->save();

    $this->addEventTestListener(function () use ($config): void {
      $config->set('include_unknown_files_in_project_root', FALSE)->save();
    });

    $stage->require(['ext-json:*']);
    $stage->apply();

    // The sandbox directory should still exist because the stage was active.
    $this->assertDirectoryExists($dir);
  }

  /**
   * Tests that cron does not delete the sandbox while the stage is active.
   *
   * If the stage is locked (not available), cron should not delete the sandbox
   * directory even if it is older than the configured maximum age.
   *
   * @legacy-covers \Drupal\package_manager\Hook\CronHook
   */
  public function testCronDoesNotDeleteWhileStageIsActive(): void {
    $stage = $this->createStage();
    $stage->create();
    $dir = $stage->getSandboxDirectory();
    $this->assertDirectoryExists($dir);

    // Set the create event timestamp to well beyond the max age.
    $stale_directory_age = time() - (30 * 24 * 60 * 60);
    $this->container->get('state')->set(LongLivedSandboxSubscriber::STATE_KEY, $stale_directory_age);

    // Run cron while the stage is still active (not destroyed).
    $this->assertFalse($stage->isAvailable());
    $this->container->get('cron')->run();

    // The sandbox directory should still exist because the stage is locked.
    $this->assertDirectoryExists($dir);
    $stage->destroy();
  }

  /**
   * Tests that config change is a no-op when no sandbox directory exists.
   *
   * When 'include_unknown_files_in_project_root' is changed to FALSE but no
   * sandbox directory exists on disk, the subscriber should do nothing.
   *
   * @legacy-covers \Drupal\package_manager\EventSubscriber\LongLivedSandboxSubscriber
   */
  public function testConfigChangeNoOpWithoutSandboxDirectory(): void {
    $stage = $this->createStage();
    $dir = $stage->getSandboxDirectory();

    // Verify no sandbox directory exists on disk.
    $this->assertDirectoryDoesNotExist($dir);

    // Changing the config setting should not cause any errors.
    $config = $this->config('package_manager.settings');
    $config->set('include_unknown_files_in_project_root', TRUE)->save();
    $config->set('include_unknown_files_in_project_root', FALSE)->save();

    // Still no directory and no errors.
    $this->assertDirectoryDoesNotExist($dir);
  }

}
