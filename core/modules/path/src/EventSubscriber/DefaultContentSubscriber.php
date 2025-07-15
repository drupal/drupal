<?php

declare(strict_types=1);

namespace Drupal\path\EventSubscriber;

use Drupal\Core\DefaultContent\PreExportEvent;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\path\Plugin\Field\FieldType\PathItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to default content-related events.
 *
 * @internal
 *   Event subscribers are internal.
 */
class DefaultContentSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected readonly EntityFieldManagerInterface $entityFieldManager,
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
    $event->setCallback('field_item:path', function (PathItem $item): array {
      $values = $item->getValue();
      // Never export the path ID; it is recreated on import.
      unset($values['pid']);
      return $values;
    });

    // Despite being computed, export path fields anyway because, even though
    // they're undergirded by path_alias entities, they're not true entity
    // references and therefore aren't portable.
    foreach ($this->entityFieldManager->getFieldMapByFieldType('path') as $path_fields_in_entity_type) {
      foreach (array_keys($path_fields_in_entity_type) as $name) {
        $event->setExportable($name, TRUE);
      }
    }
  }

}
