<?php

declare(strict_types=1);

namespace Drupal\link\EventSubscriber;

use Drupal\Core\DefaultContent\ExportMetadata;
use Drupal\Core\DefaultContent\PreExportEvent;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\link\LinkItemInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to default content-related events.
 *
 * @internal
 *   Event subscribers are internal.
 */
class DefaultContentSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
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
   * Adds an export callback for `link` field items to ensure that, if the link
   * points to a content entity, it is marked as a dependency of the entity
   * being exported.
   *
   * @param \Drupal\Core\DefaultContent\PreExportEvent $event
   *   The event object.
   */
  public function preExport(PreExportEvent $event): void {
    $event->setCallback('field_item:link', function (LinkItemInterface $item, ExportMetadata $metadata): array {
      $values = $item->getValue();

      $url = $item->getUrl();
      if (!$url->isRouted()) {
        // The URL is not routed, so there's nothing else to do.
        return $values;
      }

      $route_name = explode('.', $url->getRouteName());
      // We can rely on this pattern because routed entity URLs are generated
      // in a consistent way with the `entity` scheme.
      // @see \Drupal\Core\Url::fromUri()
      if (count($route_name) === 3 && $route_name[0] === 'entity' && $route_name[2] === 'canonical') {
        $target_entity_type_id = $route_name[1];
        $route_parameters = $url->getRouteParameters();
        $target_id = $route_parameters[$target_entity_type_id];
        $target = $this->entityTypeManager->getStorage($target_entity_type_id)
          ->load($target_id);

        if ($target instanceof ContentEntityInterface) {
          $values['target_uuid'] = $target->uuid();
          unset($values['uri']);
          $metadata->addDependency($target);
        }
      }
      return $values;
    });
  }

}
