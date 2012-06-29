<?php

namespace Drupal\Core\Config;

/**
 * Dispatches read/write operations to storage controllers.
 *
 * The storage dispatcher determines which storage out of multiple is configured
 * and allowed to handle a particular configuration object, depending on the
 * read/write operation being performed.
 *
 * The information about available storage controllers and their configuration
 * options is passed once into the constructor and normally should not change
 * within a single request or context. Special use-cases, such as import and
 * export operations, should instantiate a custom storage dispatcher tailored
 * to their specific needs.
 *
 * The storage dispatcher instantiates storage controllers on demand, and only
 * once per storage.
 *
 * @see Drupal\Core\Config\StorageInterface
 */
class StorageDispatcher {

  /**
   * Information about available storage controllers.
   *
   * @var array
   */
  protected $storageInfo;

  /**
   * Instantiated storage controller objects.
   *
   * @see Drupal\Core\Config\StorageInterface
   *
   * @var array
   */
  protected $storageInstances;

  /**
   * Constructs the storage dispatcher object.
   *
   * @param array $storage_info
   *   An associative array defining the storage controllers to use and any
   *   required configuration options for them; e.g.:
   *   @code
   *   array(
   *     'Drupal\Core\Config\DatabaseStorage' => array(
   *       'target' => 'default',
   *       'read' => TRUE,
   *       'write' => TRUE,
   *     ),
   *     'Drupal\Core\Config\FileStorage' => array(
   *       'directory' => 'sites/default/files/config',
   *       'read' => TRUE,
   *       'write' => FALSE,
   *     ),
   *   )
   *   @endcode
   */
  public function __construct(array $storage_info) {
    $this->storageInfo = $storage_info;
  }

  /**
   * Returns a storage controller to use for a given operation.
   *
   * Handles the core functionality of the storage dispatcher by determining
   * which storage can handle a particular storage access operation and
   * configuration object.
   *
   * @param string $access_operation
   *   The operation access level; either 'read' or 'write'. Use 'write' both
   *   for saving and deleting configuration.
   * @param string $name
   *   The name of the configuration object that is operated on.
   *
   * @return Drupal\Core\Config\StorageInterface
   *   The storage controller instance that can handle the requested operation.
   *
   * @throws Drupal\Core\Config\ConfigException
   *
   * @todo Allow write operations to write to multiple storages.
   */
  public function selectStorage($access_operation, $name) {
    // Determine the appropriate storage controller to use.
    // Take the first defined storage that allows $op.
    foreach ($this->storageInfo as $class => $storage_config) {
      if (!empty($storage_config[$access_operation])) {
        $storage_class = $class;
        break;
      }
    }
    if (!isset($storage_class)) {
      throw new ConfigException("Failed to find storage controller that allows $access_operation access for $name.");
    }

    // Instantiate a new storage controller object, if there is none yet.
    if (!isset($this->storageInstances[$storage_class])) {
      $this->storageInstances[$storage_class] = new $storage_class($this->storageInfo[$storage_class]);
    }
    return $this->storageInstances[$storage_class];
  }
}
