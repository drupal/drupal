<?php

declare(strict_types=1);

namespace Drupal\content_moderation\EventSubscriber;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\DefaultContent\PreExportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to default content-related events.
 *
 * @internal
 *   Event subscribers are internal.
 */
class DefaultContentSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly ModerationInformationInterface $moderationInfo,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [PreExportEvent::class => 'preExport'];
  }

  /**
   * Reacts before an entity is exported.
   *
   * @param \Drupal\Core\DefaultContent\PreExportEvent $event
   *   The event object.
   */
  public function preExport(PreExportEvent $event): void {
    $entity = $event->entity;
    if ($this->moderationInfo->isModeratedEntityType($entity->getEntityType())) {
      // The moderation_state field is not exported by default, because it is
      // computed, but for default content, we do want to preserve it.
      // @see \Drupal\content_moderation\EntityTypeInfo::entityBaseFieldInfo()
      $event->setExportable('moderation_state', TRUE);
    }
  }

}
