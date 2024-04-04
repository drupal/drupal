<?php

namespace Drupal\migrate_drupal\Plugin;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\migrate\Plugin\Exception\BadPluginDefinitionException;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Plugin manager for migrate field plugins.
 *
 * @see \Drupal\migrate_drupal\Plugin\MigrateFieldInterface
 * @see \Drupal\migrate\Annotation\MigrateField
 * @see plugin_api
 *
 * @ingroup migration
 */
class MigrateFieldPluginManager extends MigratePluginManager implements MigrateFieldPluginManagerInterface {

  /**
   * The default version of core to use for field plugins.
   *
   * These plugins were initially only built and used for Drupal 6 fields.
   * Having been extended for Drupal 7 with a "core" annotation, we fall back to
   * Drupal 6 where none exists.
   */
  const DEFAULT_CORE_VERSION = 6;

  /**
   * Get the plugin ID from the field type.
   *
   * This method determines which field plugin should be used for a given field
   * type and Drupal core version, returning the lowest weighted plugin
   * supporting the provided core version, and which matches the field type
   * either by plugin ID, or in the type_map annotation keys.
   *
   * @param string $field_type
   *   The field type being migrated.
   * @param array $configuration
   *   (optional) An array of configuration relevant to the plugin instance.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   (optional) The current migration instance.
   *
   * @return string
   *   The ID of the plugin for the field type if available.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   If the plugin cannot be determined, such as if the field type is invalid.
   *
   * @see \Drupal\migrate_drupal\Annotation\MigrateField
   */
  public function getPluginIdFromFieldType($field_type, array $configuration = [], MigrationInterface $migration = NULL) {
    $core = static::DEFAULT_CORE_VERSION;
    if (!empty($configuration['core'])) {
      $core = $configuration['core'];
    }
    elseif (!empty($migration->getPluginDefinition()['migration_tags'])) {
      foreach ($migration->getPluginDefinition()['migration_tags'] as $tag) {
        if ($tag == 'Drupal 7') {
          $core = 7;
        }
      }
    }

    $definitions = $this->getDefinitions();
    foreach ($definitions as $plugin_id => $definition) {
      if (in_array($core, $definition['core'])) {
        if (array_key_exists($field_type, $definition['type_map']) || $field_type === $plugin_id) {
          return $plugin_id;
        }
      }
    }
    throw new PluginNotFoundException($field_type);
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['core', 'source_module', 'destination_module'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new BadPluginDefinitionException($plugin_id, $required_property);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();
    $this->sortDefinitions($definitions);
    return $definitions;
  }

  /**
   * Sorts a definitions array.
   *
   * This sorts the definitions array first by the weight column, and then by
   * the plugin ID, ensuring a stable, deterministic, and testable ordering of
   * plugins.
   *
   * @param array $definitions
   *   The definitions array to sort.
   */
  protected function sortDefinitions(array &$definitions) {
    array_multisort(array_column($definitions, 'weight'), SORT_ASC, SORT_NUMERIC, array_keys($definitions), SORT_ASC, SORT_NATURAL, $definitions);
  }

}
