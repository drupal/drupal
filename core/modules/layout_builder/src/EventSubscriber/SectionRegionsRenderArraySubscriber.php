<?php

namespace Drupal\layout_builder\EventSubscriber;

use Drupal\layout_builder\Event\SectionBuildRenderArrayEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Builds the regions render array from the section components.
 *
 * @internal
 *   Tagged services are internal.
 */
class SectionRegionsRenderArraySubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SectionBuildRenderArrayEvent::class => ['onBuildRegionRenderArray', 100],
    ];
  }

  /**
   * Builds the regions render array from the section components.
   *
   * @param \Drupal\layout_builder\Event\SectionBuildRenderArrayEvent $event
   *   The section build regions render array event.
   */
  public function onBuildRegionRenderArray(SectionBuildRenderArrayEvent $event): void {
    $regions = $event->getRegions();
    foreach ($event->getSection()->getComponents() as $component) {
      if ($output = $component->toRenderArray($event->getContexts(), $event->isInPreview())) {
        $regions[$component->getRegion()][$component->getUuid()] = $output;
      }
    }
    $event->setRegions($regions);
  }

}
