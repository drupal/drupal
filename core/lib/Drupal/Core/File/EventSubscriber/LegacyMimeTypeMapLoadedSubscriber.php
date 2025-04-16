<?php

declare(strict_types=1);

namespace Drupal\Core\File\EventSubscriber;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\Event\MimeTypeMapLoadedEvent;
use Drupal\Core\File\MimeType\MimeTypeMap;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Modifies the MIME type map by calling hook_file_mimetype_mapping_alter().
 *
 * This event subscriber provides BC support for the deprecated
 * hook_file_mimetype_mapping_alter() and will be removed in drupal:12.0.0.
 *
 * @internal
 *
 * @see https://www.drupal.org/node/3494040
 */
final class LegacyMimeTypeMapLoadedSubscriber implements
  EventSubscriberInterface {

  public function __construct(
    protected readonly ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Handle the event by calling deprecated hook_file_mimetype_mapping_alter().
   */
  public function onMimeTypeMapLoaded(MimeTypeMapLoadedEvent $event): void {
    if (!$event->map instanceof MimeTypeMap) {
      return;
    }
    // @phpstan-ignore-next-line method.deprecated
    $mapping = $event->map->getMapping();
    $this->moduleHandler->alterDeprecated(
      'This hook is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Implement a \Drupal\Core\File\Event\MimeTypeMapLoadedEvent listener instead. See https://www.drupal.org/node/3494040',
      'file_mimetype_mapping',
      $mapping,
    );
    // @phpstan-ignore-next-line method.deprecated
    $event->map->setMapping($mapping);
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
