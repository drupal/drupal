<?php

declare(strict_types=1);

namespace Drupal\file_test\EventSubscriber;

use Drupal\Core\File\Event\MimeTypeMapLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

// cspell:ignore garply tarz

/**
 * Modifies the MIME type map by adding dummy mappings.
 */
class DummyMimeTypeMapLoadedSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function onMimeTypeMapLoaded(MimeTypeMapLoadedEvent $event): void {
    // Add new mappings.
    $event->map->addMapping('made_up/file_test_1', 'file_test_1');
    $event->map->addMapping('made_up/file_test_2', 'file_test_2');
    $event->map->addMapping('made_up/file_test_2', 'file_test_3');
    $event->map->addMapping('application/x-compress', 'z');
    $event->map->addMapping('application/x-tarz', 'tar.z');
    $event->map->addMapping('application/x-garply-waldo', 'garply.waldo');
    // Override existing mapping.
    $event->map->addMapping('made_up/doc', 'doc');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MimeTypeMapLoadedEvent::class => 'onMimeTypeMapLoaded',
    ];
  }

}
