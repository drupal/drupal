<?php

declare(strict_types=1);

namespace Drupal\file_validator_test\EventSubscriber;

use Drupal\file\Validation\FileValidationEvent;
use Drupal\file_test\FileTestHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a validation listener for file validation tests.
 */
class FileValidationTestSubscriber implements EventSubscriberInterface {

  /**
   * Handles the file validation event.
   *
   * @param \Drupal\file\Validation\FileValidationEvent $event
   *   The event.
   */
  public function onFileValidation(FileValidationEvent $event): void {
    FileTestHelper::logCall('validate', [$event->file->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [FileValidationEvent::class => 'onFileValidation'];
  }

}
