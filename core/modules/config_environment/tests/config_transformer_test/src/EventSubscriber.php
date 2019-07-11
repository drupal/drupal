<?php

namespace Drupal\config_transformer_test;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageTransformEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EventSubscriber.
 *
 * The transformations here are for testing purposes only and do not constitute
 * a well-behaved config storage transformation.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * The active config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $active;

  /**
   * The sync config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $sync;

  /**
   * EventSubscriber constructor.
   *
   * @param \Drupal\Core\Config\StorageInterface $active
   *   The active config storage.
   * @param \Drupal\Core\Config\StorageInterface $sync
   *   The sync config storage.
   */
  public function __construct(StorageInterface $active, StorageInterface $sync) {
    $this->active = $active;
    $this->sync = $sync;
  }

  /**
   * The storage is transformed for importing.
   *
   * In this transformation we ignore the site name from the sync storage and
   * set it always to the currently active site name with an additional string
   * so that there will always be something to import.
   * Do not do this outside of tests.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onImportTransform(StorageTransformEvent $event) {
    $storage = $event->getStorage();
    $site = $storage->read('system.site');
    // Only change something if the sync storage has data.
    if (!empty($site)) {
      // Add "Arrr" to the site name. Because pirates!
      // The site name which is in the sync directory will be ignored.
      $current = $this->active->read('system.site');
      $site['name'] = $current['name'] . ' Arrr';
      $storage->write('system.site', $site);
    }
  }

  /**
   * The storage is transformed for exporting.
   *
   * In this transformation we ignore the site slogan from the site if the sync
   * storage has it. Just export it again with an additional string so that
   * there will always be something new exported.
   * Do not do this outside of tests.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onExportTransform(StorageTransformEvent $event) {
    $sync = $this->sync->read('system.site');
    // Only change something if the sync storage has data.
    if (!empty($sync)) {
      $storage = $event->getStorage();
      $site = $storage->read('system.site');
      // Add "Arrr" to the site slogan. Because pirates!
      // The active slogan will be ignored.
      $site['slogan'] = $sync['slogan'] . ' Arrr';
      $storage->write('system.site', $site);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // @todo: use class constants when they get added in #2991683
    $events['config.transform.import'][] = ['onImportTransform'];
    $events['config.transform.export'][] = ['onExportTransform'];
    return $events;
  }

}
