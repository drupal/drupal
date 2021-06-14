<?php

namespace Drupal\file_test_upload_event\EventSubscriber;

use Drupal\file\Event\FileUploadLocationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines a test event listener for file upload location event.
 */
class UploadLocationSubscriber implements EventSubscriberInterface {

  /**
   * Listens to the FileUploadLocationEvent.
   *
   * @param \Drupal\file\Event\FileUploadLocationEvent $event
   *   Event.
   */
  public function onUploadLocationEvent(FileUploadLocationEvent $event) {
    $event->setUploadLocation(sprintf('public://%s', $event->getFormState()->getValue('folder')));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      FileUploadLocationEvent::class => ['onUploadLocationEvent'],
    ];
  }

}
