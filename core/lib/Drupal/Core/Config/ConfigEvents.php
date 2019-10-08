<?php

namespace Drupal\Core\Config;

/**
 * Defines events for the configuration system.
 *
 * @see \Drupal\Core\Config\ConfigCrudEvent
 */
final class ConfigEvents {

  /**
   * Name of the event fired when saving a configuration object.
   *
   * This event allows modules to perform an action whenever a configuration
   * object is saved. The event listener method receives a
   * \Drupal\Core\Config\ConfigCrudEvent instance.
   *
   * See hook_update_N() documentation for safe configuration API usage and
   * restrictions as this event will be fired when configuration is saved by
   * hook_update_N().
   *
   * @Event
   *
   * @see \Drupal\Core\Config\ConfigCrudEvent
   * @see \Drupal\Core\Config\Config::save()
   * @see \Drupal\Core\Config\ConfigFactory::onConfigSave()
   * @see hook_update_N()
   *
   * @var string
   */
  const SAVE = 'config.save';

  /**
   * Name of the event fired when deleting a configuration object.
   *
   * This event allows modules to perform an action whenever a configuration
   * object is deleted. The event listener method receives a
   * \Drupal\Core\Config\ConfigCrudEvent instance.
   *
   * See hook_update_N() documentation for safe configuration API usage and
   * restrictions as this event will be fired when configuration is deleted by
   * hook_update_N().
   *
   * @Event
   *
   * @see \Drupal\Core\Config\ConfigCrudEvent
   * @see \Drupal\Core\Config\Config::delete()
   * @see \Drupal\Core\Config\ConfigFactory::onConfigDelete()
   * @see hook_update_N()
   *
   * @var string
   */
  const DELETE = 'config.delete';

  /**
   * Name of the event fired when renaming a configuration object.
   *
   * This event allows modules to perform an action whenever a configuration
   * object's name is changed. The event listener method receives a
   * \Drupal\Core\Config\ConfigRenameEvent instance.
   *
   * See hook_update_N() documentation for safe configuration API usage and
   * restrictions as this event will be fired when configuration is renamed by
   * hook_update_N().
   *
   * @Event
   *
   * @see \Drupal\Core\Config\ConfigRenameEvent
   * @see \Drupal\Core\Config\ConfigFactoryInterface::rename()
   * @see hook_update_N()
   *
   * @var string
   */
  const RENAME = 'config.rename';

  /**
   * Name of the event fired when validating imported configuration.
   *
   * This event allows modules to perform additional validation operations when
   * configuration is being imported. The event listener method receives a
   * \Drupal\Core\Config\ConfigImporterEvent instance.
   *
   * @Event
   *
   * @see \Drupal\Core\Config\ConfigImporterEvent
   * @see \Drupal\Core\Config\ConfigImporter::validate().
   * @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber::onConfigImporterValidate().
   *
   * @var string
   */
  const IMPORT_VALIDATE = 'config.importer.validate';

  /**
   * Name of the event fired when importing configuration to target storage.
   *
   * This event allows modules to perform additional actions when configuration
   * is imported. The event listener method receives a
   * \Drupal\Core\Config\ConfigImporterEvent instance.
   *
   * @Event
   *
   * @see \Drupal\Core\Config\ConfigImporterEvent
   * @see \Drupal\Core\Config\ConfigImporter::import().
   * @see \Drupal\Core\EventSubscriber\ConfigSnapshotSubscriber::onConfigImporterImport().
   *
   * @var string
   */
  const IMPORT = 'config.importer.import';

  /**
   * Name of event fired when missing content dependencies are detected.
   *
   * Events subscribers are fired as part of the configuration import batch.
   * Each subscribe should call
   * \Drupal\Core\Config\MissingContentEvent::resolveMissingContent() when they
   * address a missing dependency. To address large amounts of dependencies
   * subscribers can call
   * \Drupal\Core\Config\MissingContentEvent::stopPropagation() which will stop
   * calling other events and guarantee that the configuration import batch will
   * fire the event again to continue processing missing content dependencies.
   *
   * @see \Drupal\Core\Config\ConfigImporter::processMissingContent()
   * @see \Drupal\Core\Config\MissingContentEvent
   */
  const IMPORT_MISSING_CONTENT = 'config.importer.missing_content';

  /**
   * Name of event fired to collect information on all config collections.
   *
   * This event allows modules to add to the list of configuration collections
   * retrieved by \Drupal\Core\Config\ConfigManager::getConfigCollectionInfo().
   * The event listener method receives a
   * \Drupal\Core\Config\ConfigCollectionInfo instance.
   *
   * @Event
   *
   * @see \Drupal\Core\Config\ConfigCollectionInfo
   * @see \Drupal\Core\Config\ConfigManager::getConfigCollectionInfo()
   * @see \Drupal\Core\Config\ConfigFactoryOverrideBase
   *
   * @var string
   */
  const COLLECTION_INFO = 'config.collection_info';

  /**
   * Name of the event fired just before importing configuration.
   *
   * This event allows subscribers to modify the configuration which is about to
   * be imported. The event listener method receives a
   * \Drupal\Core\Config\StorageTransformEvent instance. This event contains a
   * config storage which subscribers can interact with and which will finally
   * be used to import the configuration from.
   * Together with \Drupal\Core\Config\ConfigEvents::STORAGE_TRANSFORM_EXPORT
   * subscribers can alter the active configuration in a config sync workflow
   * instead of just overriding at runtime via the config-override system.
   * This allows a complete customisation of the workflow including additional
   * modules and editable configuration in different environments.
   *
   * @code
   *   $storage = $event->getStorage();
   * @endcode
   *
   * This event is also fired when just viewing the difference of configuration
   * to be imported independently of whether the import takes place or not.
   * Use the \Drupal\Core\Config\ConfigEvents::IMPORT event to subscribe to the
   * import having taken place.
   *
   * @Event
   *
   * @see \Drupal\Core\Config\StorageTransformEvent
   * @see \Drupal\Core\Config\ConfigEvents::STORAGE_TRANSFORM_EXPORT
   * @see \Drupal\Core\Config\ImportStorageTransformer::transform
   *
   * @var string
   */
  const STORAGE_TRANSFORM_IMPORT = 'config.transform.import';

  /**
   * Name of the event fired when the export storage is used.
   *
   * This event allows subscribers to modify the configuration which is about to
   * be exported. The event listener method receives a
   * \Drupal\Core\Config\StorageTransformEvent instance. This event contains a
   * config storage which subscribers can interact with and which will finally
   * be used to export the configuration from.
   *
   * @code
   *   $storage = $event->getStorage();
   * @endcode
   *
   * Typically subscribers will want to perform the reverse operation on the
   * storage than for \Drupal\Core\Config\ConfigEvents::STORAGE_TRANSFORM_IMPORT
   * to make sure successive exports and imports yield no difference.
   *
   * @Event
   *
   * @see \Drupal\Core\Config\StorageTransformEvent
   * @see \Drupal\Core\Config\ConfigEvents::STORAGE_TRANSFORM_IMPORT
   * @see \Drupal\Core\Config\ExportStorageManager::getStorage
   *
   * @var string
   */
  const STORAGE_TRANSFORM_EXPORT = 'config.transform.export';

}
