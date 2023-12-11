<?php

namespace Drupal\Core\Config;

/**
 * Defines events for working with configuration collections.
 *
 * Configuration collections are often used to store configuration-related
 * data, like overrides. The use case is determined by the module that provides
 * the collection. A classic example is to store the translated parts of
 * various configuration objects. Using a collection allows this data to be
 * imported and exported alongside regular configuration. It also allows the
 * data to be created when installing an extension. In both the import/export
 * and extension installation situations, collection data is stored in
 * subdirectories.
 *
 * @see \Drupal\Core\Config\ConfigCrudEvent
 */
final class ConfigCollectionEvents {

  /**
   * Event dispatched when saving configuration not in the default collection.
   *
   * This event allows modules to react whenever an object that extends
   * \Drupal\Core\Config\StorableConfigBase is saved in a non-default
   * collection. The event listener method receives a
   * \Drupal\Core\Config\ConfigCrudEvent instance.
   *
   * Note: this event is not used for configuration in the default collection.
   * See \Drupal\Core\Config\ConfigEvents::SAVE instead.
   *
   * @Event
   *
   * @var string
   *
   * @see \Drupal\Core\Config\ConfigCrudEvent
   * @see \Drupal\Core\Config\ConfigFactoryOverrideInterface::createConfigObject()
   * @see \Drupal\language\Config\LanguageConfigOverride::save()
   *
   * @see \Drupal\Core\Config\ConfigEvents::SAVE
   */
  const SAVE_IN_COLLECTION = 'config.save.collection';

  /**
   * Event dispatched when deleting configuration not in the default collection.
   *
   * This event allows modules to react whenever an object that extends
   * \Drupal\Core\Config\StorableConfigBase is deleted in a non-default
   * collection. The event listener method receives a
   * \Drupal\Core\Config\ConfigCrudEvent instance.
   *
   * Note: this event is not used for configuration in the default collection.
   * See \Drupal\Core\Config\ConfigEvents::DELETE instead.
   *
   * @Event
   *
   * @see \Drupal\Core\Config\ConfigEvents::DELETE
   * @see \Drupal\Core\Config\ConfigCrudEvent
   * @see \Drupal\Core\Config\ConfigFactoryOverrideInterface::createConfigObject()
   * @see \Drupal\language\Config\LanguageConfigOverride::delete()
   *
   * @var string
   */
  const DELETE_IN_COLLECTION = 'config.delete.collection';

  /**
   * Event dispatched when renaming configuration not in the default collection.
   *
   * This event allows modules to react whenever an object that extends
   * \Drupal\Core\Config\StorableConfigBase is renamed in a non-default
   * collection. The event listener method receives a
   * \Drupal\Core\Config\ConfigCrudEvent instance.
   *
   * Note: this event is not used for configuration in the default collection.
   * See \Drupal\Core\Config\ConfigEvents::RENAME instead.
   *
   * @Event
   *
   * @see \Drupal\Core\Config\ConfigEvents::RENAME
   * @see \Drupal\Core\Config\ConfigCrudEvent
   * @see \Drupal\Core\Config\ConfigFactoryOverrideInterface::createConfigObject()
   *
   * @var string
   */
  const RENAME_IN_COLLECTION = 'config.rename.collection';

  /**
   * Event dispatched to collect information on all config collections.
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

}
