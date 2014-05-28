<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\source\Drupal6SqlBase.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\source;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\RequirementsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base source class for Drupal migrate sources.
 *
 * Mainly to let children retrieve information from the origin system in an
 * easier way.
 */
abstract class DrupalSqlBase extends SqlBase implements ContainerFactoryPluginInterface, RequirementsInterface {

   /**
   * The contents of the system table.
   *
   * @var array
   */
  protected $systemData;

  /**
   * If the source provider is missing.
   *
   * @var bool
   */
  protected $requirements = TRUE;

  /**
    * Retrieves all system data information from origin system.
    *
    * @return array
    *   List of system table information keyed by type and name.
    */
   public function getSystemData() {
    if (!isset($this->systemData)) {
      $this->systemData = array();
      try {
        $results = $this->select('system', 's')
          ->fields('s')
          ->execute();
        foreach ($results as $result) {
          $this->systemData[$result['type']][$result['name']] = $result;
        }
      }
      catch (\Exception $e) {
        // The table might not exist for example in tests.
      }
    }
    return $this->systemData;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $plugin = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration
    );
    /** @var \Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase $plugin */
    if ($plugin_definition['requirements_met'] === TRUE) {
      if (isset($plugin_definition['source_provider'])) {
        if ($plugin->moduleExists($plugin_definition['source_provider'])) {
          if (isset($plugin_definition['minimum_schema_version']) && !$plugin->getModuleSchemaVersion($plugin_definition['source_provider']) < $plugin_definition['minimum_schema_version']) {
            $plugin->checkRequirements(FALSE);
          }
        }
        else {
          $plugin->checkRequirements(FALSE);
        }
      }
    }
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements($new_value = NULL) {
    if (isset($new_value)) {
      $this->requirements = $new_value;
    }
    return $this->requirements;
  }

  /**
   * Get a module schema_version value in the source installation.
   *
   * @param string $module
   *   Name of module.
   *
   * @return mixed
   *   The current module schema version on the origin system table or FALSE if
   *   not found.
   */
  protected function getModuleSchemaVersion($module) {
    $system_data = $this->getSystemData();
    return isset($system_data['module'][$module]['schema_version']) ? $system_data['module'][$module]['schema_version'] : FALSE;
  }

  /**
   * Check to see if a given module is enabled in the source installation.
   *
   * @param string $module
   *   Name of module to check.
   *
   * @return bool
   *   TRUE if module is enabled on the origin system, FALSE if not.
   */
  protected function moduleExists($module) {
    $system_data = $this->getSystemData();
    return !empty($system_data['module'][$module]['status']);
  }

  /**
   * Read a variable from a Drupal database.
   *
   * @param $name
   *   Name of the variable.
   * @param $default
   *   The default value.
   * @return mixed
   */
  protected function variableGet($name, $default) {
    try {
      $result = $this->select('variable', 'v')
        ->fields('v', array('value'))
        ->condition('name', $name)
        ->execute()
        ->fetchField();
    }
    // The table might not exist.
    catch (\Exception $e) {
      $result = FALSE;
    }
    return $result !== FALSE ? unserialize($result) : $default;
  }

}
