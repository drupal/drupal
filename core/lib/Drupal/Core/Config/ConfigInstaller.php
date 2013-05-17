<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigInstaller.
 */

namespace Drupal\Core\Config;

/**
 * Defines a configuration installer.
 *
 * A config installer imports the changes into the configuration system during
 * module installs.
 *
 * The ConfigInstaller has a identifier which is used to construct event names.
 * The events fired during an import are:
 * - 'config.installer.validate': Events listening can throw a
 *   \Drupal\Core\Config\ConfigImporterException to prevent an import from
 *   occurring.
 *   @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber
 * - 'config.installer.import': Events listening can react to a successful import.
 *
 * @see \Drupal\Core\Config\ConfigImporter
 */
class ConfigInstaller extends ConfigImporter {

  /**
   * The name used to identify events and the lock.
   */
  const ID = 'config.installer';

}
