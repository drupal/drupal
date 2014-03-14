<?php

/**
 * @file
 * Contains Drupal\Core\Config\Config\ConfigEvents.
 */

namespace Drupal\Core\Config;

/**
 * Defines events for the configuration system.
 */
final class ConfigEvents {

  /**
   * Name of event fired when saving the configuration object.
   *
   * @see \Drupal\Core\Config\Config::save()
   * @see \Drupal\Core\Config\ConfigFactory::onConfigSave()
   */
  const SAVE = 'config.save';

  /**
   * Name of event fired when deleting the configuration object.
   *
   * @see \Drupal\Core\Config\Config::delete()
   */
  const DELETE = 'config.delete';

  /**
   * Name of event fired when renaming a configuration object.
   *
   * @see \Drupal\Core\Config\ConfigFactory::rename().
   */
  const RENAME = 'config.rename';

  /**
   * Name of event fired when validating in the configuration import process.
   *
   * @see \Drupal\Core\Config\ConfigImporter::validate().
   * @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber::onConfigImporterValidate().
   */
  const IMPORT_VALIDATE = 'config.importer.validate';

  /**
   * Name of event fired when when importing configuration to target storage.
   *
   * @see \Drupal\Core\Config\ConfigImporter::import().
   * @see \Drupal\Core\EventSubscriber\ConfigSnapshotSubscriber::onConfigImporterImport().
   */
  const IMPORT = 'config.importer.import';

}
