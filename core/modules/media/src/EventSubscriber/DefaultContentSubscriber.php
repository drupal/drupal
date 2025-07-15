<?php

declare(strict_types=1);

namespace Drupal\media\EventSubscriber;

use Drupal\Core\DefaultContent\PreExportEvent;
use Drupal\media\MediaInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to default content-related events.
 *
 * @internal
 *   Event subscribers are internal.
 */
class DefaultContentSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [PreExportEvent::class => 'preExport'];
  }

  /**
   * Reacts before a media item is exported.
   *
   * @param \Drupal\Core\DefaultContent\PreExportEvent $event
   *   The event object.
   */
  public function preExport(PreExportEvent $event): void {
    if ($event->entity instanceof MediaInterface) {
      // Don't export the thumbnail because it is regenerated on import.
      $event->setExportable('thumbnail', FALSE);
    }
  }

}
