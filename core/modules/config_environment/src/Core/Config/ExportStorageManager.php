<?php

// @codingStandardsIgnoreStart
// @todo: Move this back to \Drupal\Core\Config in #2991683.
// Use this class with its class alias Drupal\Core\Config\ExportStorageManager
// @codingStandardsIgnoreEnd
namespace Drupal\config_environment\Core\Config;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\ReadOnlyStorage;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The export storage manager dispatches an event for the export storage.
 *
 * @internal
 */
class ExportStorageManager implements StorageManagerInterface, EventSubscriberInterface {

  use StorageCopyTrait;

  /**
   * The state key indicating that the export storage needs to be rebuilt.
   */
  const NEEDS_REBUILD_KEY = 'config_export_needs_rebuild';

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $active;

  /**
   * The drupal state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The database storage.
   *
   * @var \Drupal\Core\Config\DatabaseStorage
   */
  protected $storage;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * ExportStorageManager constructor.
   *
   * @param \Drupal\Core\Config\StorageInterface $active
   *   The active config storage to prime the export storage.
   * @param \Drupal\Core\State\StateInterface $state
   *   The drupal state.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(StorageInterface $active, StateInterface $state, Connection $connection, EventDispatcherInterface $event_dispatcher) {
    $this->active = $active;
    $this->state = $state;
    $this->eventDispatcher = $event_dispatcher;
    // The point of this service is to provide the storage and dispatch the
    // event when needed, so the storage itself can not be a service.
    $this->storage = new DatabaseStorage($connection, 'config_export');
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage() {
    if ($this->state->get(self::NEEDS_REBUILD_KEY, TRUE)) {
      self::replaceStorageContents($this->active, $this->storage);
      // @todo: Use ConfigEvents::STORAGE_TRANSFORM_EXPORT in #2991683
      $this->eventDispatcher->dispatch('config.transform.export', new StorageTransformEvent($this->storage));
      $this->state->set(self::NEEDS_REBUILD_KEY, FALSE);
    }

    return new ReadOnlyStorage($this->storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onConfigChange', 0];
    $events[ConfigEvents::DELETE][] = ['onConfigChange', 0];
    $events[ConfigEvents::RENAME][] = ['onConfigChange', 0];
    return $events;
  }

  /**
   * Set the flag in state that the export storage is out of date.
   */
  public function onConfigChange() {
    if (!$this->state->get(self::NEEDS_REBUILD_KEY, FALSE)) {
      $this->state->set(self::NEEDS_REBUILD_KEY, TRUE);
    }
  }

}
