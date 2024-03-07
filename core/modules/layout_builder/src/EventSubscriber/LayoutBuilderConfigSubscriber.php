<?php

declare(strict_types=1);

namespace Drupal\layout_builder\EventSubscriber;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Layout Builder Config subscriber.
 */
final class LayoutBuilderConfigSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a LayoutBuilderConfigSubscriber.
   */
  public function __construct(
    protected BlockManagerInterface $blockManager,
  ) {
  }

  /**
   * Clears the block plugin cache when expose_all_field_blocks changes.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    $saved_config = $event->getConfig();
    if ($saved_config->getName() == 'layout_builder.settings' && $event->isChanged('expose_all_field_blocks')) {
      $this->blockManager->clearCachedDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[ConfigEvents::SAVE][] = ['onConfigSave'];
    return $events;
  }

}
