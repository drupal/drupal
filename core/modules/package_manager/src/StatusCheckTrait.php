<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Drupal\package_manager\Event\StatusCheckEvent;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Contains helper methods to run status checks on a stage.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not use or interact with
 *   this trait.
 */
trait StatusCheckTrait {

  /**
   * Runs a status check for a stage and returns the results, if any.
   *
   * @param \Drupal\package_manager\StageBase $stage
   *   The stage to run the status check for.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface|null $event_dispatcher
   *   (optional) The event dispatcher service.
   * @param \Drupal\package_manager\PathLocator|null $path_locator
   *   (optional) The path locator service.
   * @param \PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface|null $path_factory
   *   (optional) The path factory service.
   *
   * @return \Drupal\package_manager\ValidationResult[]
   *   The results of the status check. If a readiness check was also done,
   *   its results will be included.
   */
  protected function runStatusCheck(StageBase $stage, ?EventDispatcherInterface $event_dispatcher = NULL, ?PathLocator $path_locator = NULL, ?PathFactoryInterface $path_factory = NULL): array {
    $event_dispatcher ??= \Drupal::service('event_dispatcher');
    $path_locator ??= \Drupal::service(PathLocator::class);
    $path_factory ??= \Drupal::service(PathFactoryInterface::class);
    try {
      $paths_to_exclude_event = new CollectPathsToExcludeEvent($stage, $path_locator, $path_factory);
      $event_dispatcher->dispatch($paths_to_exclude_event);
    }
    catch (\Throwable $throwable) {
      $paths_to_exclude_event = $throwable;
    }
    $event = new StatusCheckEvent($stage, $paths_to_exclude_event);
    return $event_dispatcher->dispatch($event)->getResults();
  }

}
