<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Drupal\package_manager\Event\StatusCheckEvent;
use Drupal\package_manager\StatusCheckTrait;
use Drupal\package_manager\ValidationResult;

/**
 * @covers \Drupal\package_manager\StatusCheckTrait
 * @group package_manager
 * @internal
 */
class StatusCheckTraitTest extends PackageManagerKernelTestBase {

  use StatusCheckTrait;

  /**
   * Tests that StatusCheckTrait will collect paths to exclude.
   */
  public function testPathsToExcludeCollected(): void {
    $this->addEventTestListener(function (CollectPathsToExcludeEvent $event): void {
      $event->add('/junk/drawer');
    }, CollectPathsToExcludeEvent::class);

    $status_check_called = FALSE;
    $this->addEventTestListener(function (StatusCheckEvent $event) use (&$status_check_called): void {
      $this->assertContains('/junk/drawer', $event->excludedPaths->getAll());
      $status_check_called = TRUE;
    }, StatusCheckEvent::class);
    $this->runStatusCheck($this->createStage(), $this->container->get('event_dispatcher'));
    $this->assertTrue($status_check_called);
  }

  /**
   * Tests that any error will be added to the status check event.
   */
  public function testNoErrorIfPathsToExcludeCannotBeCollected(): void {
    $e = new \Exception('Not a chance, friend.');

    $listener = function () use ($e): never {
      throw $e;
    };
    $this->addEventTestListener($listener, CollectPathsToExcludeEvent::class);

    $excluded_paths_are_null = FALSE;
    $listener = function (StatusCheckEvent $event) use (&$excluded_paths_are_null): void {
      $excluded_paths_are_null = is_null($event->excludedPaths);
    };
    $this->addEventTestListener($listener, StatusCheckEvent::class);

    $this->assertStatusCheckResults([
      ValidationResult::createErrorFromThrowable($e),
    ]);
    $this->assertTrue($excluded_paths_are_null);
  }

}
