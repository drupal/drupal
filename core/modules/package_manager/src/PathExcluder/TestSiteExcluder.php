<?php

declare(strict_types=1);

namespace Drupal\package_manager\PathExcluder;

use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Excludes 'sites/simpletest' from stage operations.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class TestSiteExcluder implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CollectPathsToExcludeEvent::class => 'excludeTestSites',
    ];
  }

  /**
   * Excludes sites/simpletest from stage operations.
   *
   * @param \Drupal\package_manager\Event\CollectPathsToExcludeEvent $event
   *   The event object.
   */
  public function excludeTestSites(CollectPathsToExcludeEvent $event): void {
    // Always exclude automated test directories. If they exist, they will be in
    // the web root.
    $event->addPathsRelativeToWebRoot(['sites/simpletest']);
  }

}
