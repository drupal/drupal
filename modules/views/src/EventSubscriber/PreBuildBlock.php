<?php

namespace Drupal\views\EventSubscriber;

use Drupal\views\Event\PreBuildBlockEvent;
use Drupal\views\ViewsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PreBuildBlock implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ViewsEvents::DISPLAY_BLOCK_PRE_BUILD_BLOCK][] = ['onPreBlockBuild', 0];
    return $events;
  }

  public function onPreBlockBuild(PreBuildBlockEvent $event) {
    $block = $event->getBlock();

    $config = $block->getConfiguration();

    if ($config['items_per_page'] !== 'none') {
      $display = $event->getDisplay();
      $display->view->setItemsPerPage($config['items_per_page']);
    }

  }
}
