<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Exception\StageEventException;
use Drupal\package_manager\ValidationResult;

/**
 * @covers \Drupal\package_manager\Validator\PendingUpdatesValidator
 * @group package_manager
 * @internal
 */
class PendingUpdatesValidatorTest extends PackageManagerKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests that no error is raised if there are no pending updates.
   */
  public function testNoPendingUpdates(): void {
    $this->assertStatusCheckResults([]);
    $this->assertResults([], PreCreateEvent::class);
  }

  /**
   * Tests that an error is raised if there are pending schema updates.
   *
   * @depends testNoPendingUpdates
   */
  public function testPendingUpdateHook(): void {
    // Set the installed schema version of Package Manager to its default value
    // and import an empty update hook which is numbered much higher than will
    // ever exist in the real world.
    $this->container->get('keyvalue')
      ->get('system.schema')
      ->set('package_manager', \Drupal::CORE_MINIMUM_SCHEMA_VERSION);

    require_once __DIR__ . '/../../fixtures/db_update.php';

    $result = ValidationResult::createError([
      t('Some modules have database updates pending. You should run the <a href="/update.php">database update script</a> immediately.'),
    ]);
    $this->assertStatusCheckResults([$result]);
    $this->assertResults([$result], PreCreateEvent::class);
  }

  /**
   * Tests that an error is raised if there are pending post-updates.
   */
  public function testPendingPostUpdate(): void {
    // Make an additional post-update function available; the update registry
    // will think it's pending.
    require_once __DIR__ . '/../../fixtures/post_update.php';
    $result = ValidationResult::createError([
      t('Some modules have database updates pending. You should run the <a href="/update.php">database update script</a> immediately.'),
    ]);
    $this->assertStatusCheckResults([$result]);
    $this->assertResults([$result], PreCreateEvent::class);
  }

  /**
   * Tests that pending updates stop an operation from being applied.
   */
  public function testPendingUpdateAfterStaged(): void {
    $stage = $this->createStage();
    $stage->create();
    $stage->require(['drupal/core:9.8.1']);
    // Make an additional post-update function available; the update registry
    // will think it's pending.
    require_once __DIR__ . '/../../fixtures/post_update.php';
    $result = ValidationResult::createError([
      t('Some modules have database updates pending. You should run the <a href="/update.php">database update script</a> immediately.'),
    ]);
    try {
      $stage->apply();
      $this->fail('Able to apply update even though there is pending update.');
    }
    catch (StageEventException $exception) {
      $this->assertExpectedResultsFromException([$result], $exception);
    }
  }

}
