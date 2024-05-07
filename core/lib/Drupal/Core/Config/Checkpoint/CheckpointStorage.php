<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Checkpoint;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigCollectionEvents;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigRenameEvent;
use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a config storage that can make checkpoints.
 *
 * This storage wraps the active storage, and provides the ability to take
 * checkpoints. Once a checkpoint has been created all configuration operations
 * made after the checkpoint will be recorded, so it is possible to revert to
 * original state when the checkpoint was taken.
 *
 * This class cannot be used to checkpoint another storage since it relies on
 * events triggered by the configuration system in order to work. It is the
 * responsibility of the caller to construct this class with the active storage.
 *
 * @internal
 *   This API is experimental.
 */
final class CheckpointStorage implements CheckpointStorageInterface, EventSubscriberInterface, LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * Used as prefix to a config checkpoint collection.
   *
   * If this code is copied in order to checkpoint a different storage then
   * this value must be changed.
   */
  private const KEY_VALUE_COLLECTION_PREFIX = 'config.checkpoint.';

  /**
   * Used to store the list of collections in each checkpoint.
   *
   * Note this cannot be a valid configuration name.
   *
   * @see \Drupal\Core\Config\ConfigBase::validateName()
   */
  private const CONFIG_COLLECTION_KEY = 'collections';

  /**
   * The key value stores that store configuration changed for each checkpoint.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface[]
   */
  private array $keyValueStores;

  /**
   * The checkpoint to read from.
   *
   * @var \Drupal\Core\Config\Checkpoint\Checkpoint|null
   */
  private ?Checkpoint $readFromCheckpoint = NULL;

  /**
   * Constructs a CheckpointStorage object.
   *
   * @param \Drupal\Core\Config\StorageInterface $activeStorage
   *   The active configuration storage.
   * @param \Drupal\Core\Config\Checkpoint\CheckpointListInterface $checkpoints
   *   The list of checkpoints.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $keyValueFactory
   *   The key value factory.
   * @param string $collection
   *   (optional) The configuration collection.
   */
  public function __construct(
    private readonly StorageInterface $activeStorage,
    private readonly CheckpointListInterface $checkpoints,
    private readonly KeyValueFactoryInterface $keyValueFactory,
    private readonly string $collection = StorageInterface::DEFAULT_COLLECTION,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    if (count($this->checkpoints) === 0) {
      throw new NoCheckpointsException();
    }

    foreach ($this->getCheckpointsToReadFrom() as $checkpoint) {
      $in_checkpoint = $this->getKeyValue($checkpoint->id, $this->collection)->get($name);
      if ($in_checkpoint !== NULL) {
        // If $in_checkpoint is FALSE then the configuration has been deleted.
        return $in_checkpoint !== FALSE;
      }
    }
    return $this->activeStorage->exists($name);
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    $return = $this->readMultiple([$name]);
    return $return[$name] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    if (count($this->checkpoints) === 0) {
      throw new NoCheckpointsException();
    }
    $return = [];

    foreach ($this->getCheckpointsToReadFrom() as $checkpoint) {
      $return = array_merge(
        $return,
        $this->getKeyValue($checkpoint->id, $this->collection)->getMultiple($names)
      );
      // Remove the read names from the list to fetch.
      $names = array_diff($names, array_keys($return));
      if (empty($names)) {
        // All the configuration has been read. Nothing more to do.
        break;
      }
    }

    // Names not found in the checkpoints have not been modified: read from
    // active storage.
    if (!empty($names)) {
      $return = array_merge(
        $return,
        $this->activeStorage->readMultiple($names)
      );
    }

    // Remove any renamed or new configuration (FALSE has been recorded for
    // these operations in the checkpoint).
    // @see ::onConfigRename()
    // @see ::onConfigSaveAndDelete()
    return array_filter($return);
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data) {
    return $this->activeStorage->encode($data);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw) {
    return $this->activeStorage->decode($raw);
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    if (count($this->checkpoints) === 0) {
      throw new NoCheckpointsException();
    }

    $names = $new_configuration = [];

    foreach ($this->getCheckpointsToReadFrom() as $checkpoint) {
      $checkpoint_names = array_keys(array_filter($this->getKeyValue($checkpoint->id, $this->collection)->getAll(), function (mixed $value, string $name) use (&$new_configuration, $prefix) {
        if ($name === static::CONFIG_COLLECTION_KEY) {
          return FALSE;
        }
        // Remove any that don't start with the prefix.
        if ($prefix !== '' && !str_starts_with($name, $prefix)) {
          return FALSE;
        }
        // We've determined in a previous checkpoint that the configuration did
        // not exist.
        if (in_array($name, $new_configuration, TRUE)) {
          return FALSE;
        }
        // If the value is FALSE then the configuration was created after the
        // checkpoint.
        if ($value === FALSE) {
          $new_configuration[] = $name;
          return FALSE;
        }
        return TRUE;
      }, ARRAY_FILTER_USE_BOTH));
      $names = array_merge($names, $checkpoint_names);
    }

    // Remove any names that did not exist prior to the checkpoint.
    $active_names = array_diff($this->activeStorage->listAll($prefix), $new_configuration);

    $names = array_unique(array_merge($names, $active_names));
    sort($names);
    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    $collection = new self(
      $this->activeStorage->createCollection($collection),
      $this->checkpoints,
      $this->keyValueFactory,
      $collection
    );
    // \Drupal\Core\Config\Checkpoint\CheckpointStorage::$readFromCheckpoint is
    // assigned by reference so that it is  consistent across all collection
    // objects created from the same initial object.
    $collection->readFromCheckpoint = &$this->readFromCheckpoint;
    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames() {
    $names = [];
    foreach ($this->getCheckpointsToReadFrom() as $checkpoint) {
      $names = array_merge(
        $names,
        $this->getKeyValue($checkpoint->id, StorageInterface::DEFAULT_COLLECTION)->get(static::CONFIG_COLLECTION_KEY, [])
      );
    }
    return array_unique(array_merge($this->activeStorage->getAllCollectionNames(), $names));
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName() {
    return $this->collection;
  }

  /**
   * {@inheritdoc}
   */
  public function checkpoint(string|\Stringable $label): Checkpoint {
    // Generate a new ID based on the state of the current active checkpoint.
    $active_checkpoint = $this->checkpoints->getActiveCheckpoint();
    if (!$active_checkpoint instanceof Checkpoint) {
      // @todo https://www.drupal.org/i/3408525 Consider options for generating
      //   a real fingerprint.
      $id = hash('sha1', random_bytes(32));
      return $this->checkpoints->add($id, $label);
    }

    // Determine if we need to create a new checkpoint by checking if
    // configuration has changed since the last checkpoint.
    $collections = $this->getAllCollectionNames();
    $collections[] = StorageInterface::DEFAULT_COLLECTION;
    foreach ($collections as $collection) {
      $current_checkpoint_data[$collection] = $this->getKeyValue($active_checkpoint->id, $collection)->getAll();
      // Remove the collections key because it is irrelevant.
      unset($current_checkpoint_data[$collection][static::CONFIG_COLLECTION_KEY]);
      // If there is no data in the collection then there is no need to hash
      // the empty array.
      if (empty($current_checkpoint_data[$collection])) {
        unset($current_checkpoint_data[$collection]);
      }
    }

    if (!empty($current_checkpoint_data)) {
      // Use json_encode() here because it is both quicker and results in
      // smaller output than serialize().
      $id = hash('sha1', ($active_checkpoint->parent ?? '') . json_encode($current_checkpoint_data));
      return $this->checkpoints->add($id, $label);
    }

    $this->logger?->notice('A backup checkpoint was not created because nothing has changed since the "{active}" checkpoint was created.', [
      'active' => $active_checkpoint->label,
    ]);
    return $active_checkpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function setCheckpointToReadFrom(string|Checkpoint $checkpoint_id): static {
    if ($checkpoint_id instanceof Checkpoint) {
      $checkpoint_id = $checkpoint_id->id;
    }
    $this->readFromCheckpoint = $this->checkpoints->get($checkpoint_id);
    return $this;
  }

  /**
   * Gets the key value storage for the provided checkpoint.
   *
   * @param string $checkpoint
   *   The checkpoint to get the key value storage for.
   * @param string $collection
   *   The config collection to get the key value storage for.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   *   The key value storage for the provided checkpoint.
   */
  private function getKeyValue(string $checkpoint, string $collection): KeyValueStoreInterface {
    $checkpoint_key = $checkpoint;
    if ($collection !== StorageInterface::DEFAULT_COLLECTION) {
      $checkpoint_key = $collection . '.' . $checkpoint_key;
    }
    return $this->keyValueStores[$checkpoint_key] ??= $this->keyValueFactory->get(self::KEY_VALUE_COLLECTION_PREFIX . $checkpoint_key);
  }

  /**
   * Gets the checkpoints to read from.
   *
   * @return \Traversable<string, \Drupal\Core\Config\Checkpoint\Checkpoint>
   *   The checkpoints, keyed by ID.
   */
  private function getCheckpointsToReadFrom(): \Traversable {
    $checkpoint = $this->checkpoints->getActiveCheckpoint();

    /** @var \Drupal\Core\Config\Checkpoint\Checkpoint[] $checkpoints_to_read_from */
    $checkpoints_to_read_from = [$checkpoint];
    if ($checkpoint->id !== $this->readFromCheckpoint?->id) {
      // Follow ancestors to find the checkpoint to start reading from.
      foreach ($this->checkpoints->getParents($checkpoint->id) as $checkpoint) {
        array_unshift($checkpoints_to_read_from, $checkpoint);
        if ($checkpoint->id === $this->readFromCheckpoint?->id) {
          break;
        }
      }
    }

    // Replay in parent to child order.
    foreach ($checkpoints_to_read_from as $checkpoint) {
      yield $checkpoint->id => $checkpoint;
    }
  }

  /**
   * Updates checkpoint when configuration is saved.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSaveAndDelete(ConfigCrudEvent $event): void {
    $active_checkpoint = $this->checkpoints->getActiveCheckpoint();
    if ($active_checkpoint === NULL) {
      return;
    }

    $saved_config = $event->getConfig();
    $collection = $saved_config->getStorage()->getCollectionName();
    $this->storeCollectionName($collection);

    $key_value = $this->getKeyValue($active_checkpoint->id, $collection);

    // If we have not yet stored a checkpoint for this configuration we should.
    if ($key_value->get($saved_config->getName()) === NULL) {
      $original_data = $this->getOriginalConfig($saved_config);
      // An empty array indicates that the config has to be new as a sequence
      // cannot be the root of a config object. We need to make this assumption
      // because $saved_config->isNew() will always return FALSE here.
      if (empty($original_data)) {
        $original_data = FALSE;
      }
      // Only save change to state if there is a change, even if it's just keys
      // being re-ordered.
      if ($original_data !== $saved_config->getRawData()) {
        $key_value->set($saved_config->getName(), $original_data);
      }
    }
  }

  /**
   * Updates checkpoint when configuration is saved.
   *
   * @param \Drupal\Core\Config\ConfigRenameEvent $event
   *   The configuration event.
   */
  public function onConfigRename(ConfigRenameEvent $event): void {
    $active_checkpoint = $this->checkpoints->getActiveCheckpoint();
    if ($active_checkpoint === NULL) {
      return;
    }
    $collection = $event->getConfig()->getStorage()->getCollectionName();
    $this->storeCollectionName($collection);

    $key_value = $this->getKeyValue($active_checkpoint->id, $collection);

    $old_name = $event->getOldName();

    // If we have not yet stored a checkpoint for this configuration, store a
    // complete copy of the original configuration. Note that renames do not
    // change data but storing the complete data allows
    // \Drupal\Core\Config\ConfigImporter to track renames using UUIDs.
    if ($key_value->get($old_name) === NULL) {
      $key_value->set($old_name, $this->getOriginalConfig($event->getConfig()));
    }

    // Record that the new name did not exist prior to the checkpoint.
    $new_name = $event->getConfig()->getName();
    if ($key_value->get($new_name) === NULL) {
      $key_value->set($new_name, FALSE);
    }
  }

  /**
   * Gets the original data from the configuration.
   *
   * @param \Drupal\Core\Config\StorableConfigBase $config
   *   The config to get the original data from.
   *
   * @return mixed
   *   The original data.
   */
  private function getOriginalConfig(StorableConfigBase $config): mixed {
    if ($config instanceof Config) {
      return $config->getOriginal(apply_overrides: FALSE);
    }
    return $config->getOriginal();
  }

  /**
   * Stores the collection name so the storage knows its own collections.
   *
   * @param string $collection
   *   The name of the collection.
   */
  private function storeCollectionName(string $collection): void {
    // We do not need to store the default collection.
    if ($collection === StorageInterface::DEFAULT_COLLECTION) {
      return;
    }

    $key_value = $this->getKeyValue($this->checkpoints->getActiveCheckpoint()->id, StorageInterface::DEFAULT_COLLECTION);
    $collections = $key_value->get(static::CONFIG_COLLECTION_KEY, []);
    assert(is_array($collections));
    if (in_array($collection, $collections, TRUE)) {
      return;
    }
    $collections[] = $collection;
    $key_value->set(static::CONFIG_COLLECTION_KEY, $collections);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[ConfigEvents::SAVE][] = 'onConfigSaveAndDelete';
    $events[ConfigEvents::DELETE][] = 'onConfigSaveAndDelete';
    $events[ConfigEvents::RENAME][] = 'onConfigRename';
    $events[ConfigCollectionEvents::SAVE_IN_COLLECTION][] = 'onConfigSaveAndDelete';
    $events[ConfigCollectionEvents::DELETE_IN_COLLECTION][] = 'onConfigSaveAndDelete';
    $events[ConfigCollectionEvents::RENAME_IN_COLLECTION][] = 'onConfigRename';
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data): never {
    throw new \BadMethodCallException(__METHOD__ . ' is not allowed on a CheckpointStorage');
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name): never {
    throw new \BadMethodCallException(__METHOD__ . ' is not allowed on a CheckpointStorage');
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name): never {
    throw new \BadMethodCallException(__METHOD__ . ' is not allowed on a CheckpointStorage');
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = ''): never {
    throw new \BadMethodCallException(__METHOD__ . ' is not allowed on a CheckpointStorage');
  }

}
