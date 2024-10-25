<?php

declare(strict_types=1);

namespace Drupal\package_manager\PathExcluder;

use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Excludes node_modules files from stage directories.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
class NodeModulesExcluder implements EventSubscriberInterface {

  /**
   * Excludes node_modules directories from stage operations.
   *
   * @param \Drupal\package_manager\Event\CollectPathsToExcludeEvent $event
   *   The event object.
   */
  public function excludeNodeModulesFiles(CollectPathsToExcludeEvent $event): void {
    $event->addPathsRelativeToProjectRoot($event->scanForDirectoriesByName('node_modules'));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CollectPathsToExcludeEvent::class => 'excludeNodeModulesFiles',
    ];
  }

}
