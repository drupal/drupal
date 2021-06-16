<?php

namespace Drupal\block\EventSubscriber;

use Drupal\Core\Render\PageDisplayVariantSelectionEvent;
use Drupal\Core\Render\RenderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Selects the block page display variant.
 *
 * @see \Drupal\block\Plugin\DisplayVariant\BlockPageVariant
 */
class BlockPageDisplayVariantSubscriber implements EventSubscriberInterface {

  /**
   * Selects the block page display variant.
   *
   * @param \Drupal\Core\Render\PageDisplayVariantSelectionEvent $event
   *   The event to process.
   */
  public function onSelectPageDisplayVariant(PageDisplayVariantSelectionEvent $event) {
    $event->setPluginId('block_page');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RenderEvents::SELECT_PAGE_DISPLAY_VARIANT][] = ['onSelectPageDisplayVariant'];
    return $events;
  }

}
