<?php

declare(strict_types=1);

namespace Drupal\layout_builder\EventSubscriber;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Core\DefaultContent\ExportMetadata;
use Drupal\Core\DefaultContent\PreExportEvent;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\layout_builder\Plugin\Field\FieldType\LayoutSectionItem;
use Drupal\layout_builder\Section;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to default content-related events.
 *
 * @internal
 *   Event subscribers are internal.
 */
class DefaultContentSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly EntityRepositoryInterface $entityRepository,
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
   * Adds an export callback for `layout_section` field items to ensure that
   * any referenced block content entities are marked as dependencies of the
   * entity being exported.
   *
   * @param \Drupal\Core\DefaultContent\PreExportEvent $event
   *   The event object.
   */
  public function preExport(PreExportEvent $event): void {
    $event->setCallback('field_item:layout_section', function (LayoutSectionItem $item, ExportMetadata $metadata): array {
      $section = $item->get('section')->getValue();
      assert($section instanceof Section);

      foreach ($section->getComponents() as $component) {
        $plugin = $component->getPlugin();
        if ($plugin instanceof DerivativeInspectionInterface && $plugin->getBaseId() === 'block_content') {
          $block_content = $this->entityRepository->loadEntityByUuid('block_content', $plugin->getDerivativeId());
          if ($block_content) {
            $metadata->addDependency($block_content);
          }
        }
      }
      return [
        'section' => $section->toArray(),
      ];
    });
  }

}
