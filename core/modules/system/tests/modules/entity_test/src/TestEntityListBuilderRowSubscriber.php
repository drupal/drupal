<?php

namespace Drupal\entity_test;


use Drupal\Core\Entity\EntityListBuilderRowEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestEntityListBuilderRowSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      EntityListBuilderRowEvent::class => 'alterRow',
    ];
  }

  /**
   * Alters the list builder row.
   *
   * @param EntityListBuilderRowEvent $event
   *   The event instance.
   */
  public function alterRow(EntityListBuilderRowEvent $event): void {
    if (\Drupal::state()->get('entity_test.list_builder.allow_altering')) {
      $row = $event->getRow();
      $row['label'] = "Altered row: {$row['label']}";
      $event->setRow($row);
    }
  }

}
