<?php

namespace Drupal\node\EventSubscriber;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Event\EventBase;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Creates a key value collection for migrated node translation mappings.
 *
 * If we are migrating node translations from Drupal 6 or 7, these nodes will be
 * combined with their source node. Since there still might be references to the
 * URLs of these now consolidated nodes, this service saves the mapping between
 * the old nids to the new ones to be able to redirect them to the right node in
 * the right language.
 *
 * The mapping is stored in the "node_translation_redirect" key/value collection
 * and the redirection is made by the NodeTranslationExceptionSubscriber class.
 *
 * @see \Drupal\node\NodeServiceProvider
 * @see \Drupal\node\EventSubscriber\NodeTranslationExceptionSubscriber
 */
class NodeTranslationMigrateSubscriber implements EventSubscriberInterface {

  /**
   * The key value factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs the NodeTranslationMigrateSubscriber.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   *   The key value factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(KeyValueFactoryInterface $key_value, StateInterface $state) {
    $this->keyValue = $key_value;
    $this->state = $state;
  }

  /**
   * Helper method to check if we are migrating translated nodes.
   *
   * @param \Drupal\migrate\Event\EventBase $event
   *   The migrate event.
   *
   * @return bool
   *   True if we are migrating translated nodes, false otherwise.
   */
  protected function isNodeTranslationsMigration(EventBase $event) {
    $migration = $event->getMigration();
    $source_configuration = $migration->getSourceConfiguration();
    $destination_configuration = $migration->getDestinationConfiguration();
    return !empty($source_configuration['translations']) && $destination_configuration['plugin'] === 'entity:node';
  }

  /**
   * Maps the old nid to the new one in the key value collection.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The migrate post row save event.
   */
  public function onPostRowSave(MigratePostRowSaveEvent $event) {
    if ($this->isNodeTranslationsMigration($event)) {
      $row = $event->getRow();
      $source = $row->getSource();
      $destination = $row->getDestination();
      $collection = $this->keyValue->get('node_translation_redirect');
      $collection->set($source['nid'], [$destination['nid'], $destination['langcode']]);
    }
  }

  /**
   * Set the node_translation_redirect state to enable the redirects.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The migrate import event.
   */
  public function onPostImport(MigrateImportEvent $event) {
    if ($this->isNodeTranslationsMigration($event)) {
      $this->state->set('node_translation_redirect', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];

    $events[MigrateEvents::POST_ROW_SAVE] = ['onPostRowSave'];
    $events[MigrateEvents::POST_IMPORT] = ['onPostImport'];

    return $events;
  }

}
