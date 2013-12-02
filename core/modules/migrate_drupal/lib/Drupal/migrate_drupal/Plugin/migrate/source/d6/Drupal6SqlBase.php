<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\source\d6\Drupal6SqlBase.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * A base source class for Drupal 6 migrate sources.
 *
 * Mainly to let children retrieve information from the origin system in an
 * easier way.
 */
abstract class Drupal6SqlBase extends SqlBase {

  /**
   * Retrieves all system data information from origin system.
   *
   * @return array
   *   List of system table information keyed by type and name.
   */
  public function getSystemData() {
    static $system_data;
    if (isset($system_data)) {
      return $system_data;
    }
    $results = $this->database
      ->select('system', 's')
      ->fields('s')
      ->execute();
    foreach ($results as $result) {
      $system_data[$result['type']][$result['name']] = $result;
    }
    return $system_data;
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
    return isset($system_data['module'][$module]['status']) ? (bool) $system_data['module'][$module]['status'] : FALSE;
  }

  protected function variableGet($name, $default) {
    try {
      $result = $this->database
        ->query('SELECT value FROM {variable} WHERE name = :name', array(':name' => $name))
        ->fetchField();
    }
    // The table might not exist.
    catch (\Exception $e) {
      $result = FALSE;
    }
    return $result !== FALSE ? unserialize($result) : $default;
  }

}
