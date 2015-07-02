<?php

/**
 * @file
 * Contains \Drupal\migrate\MigrateTemplateStorage.
 */

namespace Drupal\migrate;

use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Storage to access migration template configuration in enabled extensions.
 */
class MigrateTemplateStorage extends ExtensionInstallStorage {

  /**
   * Extension sub-directory containing default configuration for migrations.
   */
  const MIGRATION_TEMPLATE_DIRECTORY = 'migration_templates';

  /**
   * {@inheritdoc}
   */
  public function __construct(StorageInterface $config_storage, $directory = self::MIGRATION_TEMPLATE_DIRECTORY, $collection = StorageInterface::DEFAULT_COLLECTION, $include_profile = TRUE) {
    parent::__construct($config_storage, $directory, $collection, $include_profile);
  }

  /**
   * Find all migration templates with the specified tag.
   *
   * @param $tag
   *   The tag to match.
   *
   * @return array
   *   Any templates (parsed YAML config) that matched, keyed by the ID.
   */
  public function findTemplatesByTag($tag) {
    $templates = $this->getAllTemplates();
    $matched_templates = [];
    foreach ($templates as $template_name => $template) {
      if (!empty($template['migration_tags'])) {
        if (in_array($tag, $template['migration_tags'])) {
          $matched_templates[$template_name] = $template;
        }
      }
    }
    return $matched_templates;
  }

  /**
   * Retrieves all migration templates belonging to enabled extensions.
   *
   * @return array
   *   Array of parsed templates, keyed by the fully-qualified id.
   */
  public function getAllTemplates() {
    // Retrieve the full list of templates, keyed by fully-qualified name,
    // with the containing folder as the value.
    $folders = $this->getAllFolders();
    $templates = [];
    foreach ($folders as $full_name => $folder) {
      // The fully qualified name will be in the form migrate.migration.d6_node.
      // Break out the provider ('migrate') and name ('migration.d6_node').
      list($provider, $name) = explode('.', $full_name, 2);
      // Retrieve and parse the template contents.
      $discovery = new YamlDiscovery($name, array($provider => $folder));
      $all = $discovery->findAll();
      $templates[$full_name] = reset($all);
    }

    return $templates;
  }

}
