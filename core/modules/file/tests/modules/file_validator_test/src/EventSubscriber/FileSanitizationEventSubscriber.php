<?php

declare(strict_types=1);

namespace Drupal\file_validator_test\EventSubscriber;

use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a file sanitization listener for file upload tests.
 */
class FileSanitizationEventSubscriber implements EventSubscriberInterface {

  /**
   * The allowed extensions.
   *
   * @var string[]
   */
  protected array $allowedExtensions = [];

  /**
   * Handles the file sanitization event.
   */
  public function onFileSanitization(FileUploadSanitizeNameEvent $event) {
    $this->allowedExtensions = $event->getAllowedExtensions();
  }

  /**
   * Gets the allowed extensions.
   *
   * @return string[]
   *   The allowed extensions.
   */
  public function getAllowedExtensions(): array {
    return $this->allowedExtensions;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [FileUploadSanitizeNameEvent::class => 'onFileSanitization'];
  }

}
