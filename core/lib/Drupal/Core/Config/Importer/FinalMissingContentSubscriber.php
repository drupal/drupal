<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Importer\FinalMissingContentSubscriber.
 */

namespace Drupal\Core\Config\Importer;

use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Final event subscriber to the missing content event.
 *
 * Ensure that all missing content dependencies are removed from the event so
 * the importer can complete.
 *
 * @see \Drupal\Core\Config\ConfigImporter::processMissingContent()
 */
class FinalMissingContentSubscriber implements EventSubscriberInterface {

  /**
   * Handles the missing content event.
   *
   * @param \Drupal\Core\Config\Importer\MissingContentEvent $event
   *   The missing content event.
   */
  public function onMissingContent(MissingContentEvent $event) {
    foreach (array_keys($event->getMissingContent()) as $uuid) {
      $event->resolveMissingContent($uuid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // This should always be the final event as it will mark all content
    // dependencies as resolved.
    $events[ConfigEvents::IMPORT_MISSING_CONTENT][] = array('onMissingContent', -1024);
    return $events;
  }

}
