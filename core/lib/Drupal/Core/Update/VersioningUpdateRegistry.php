<?php

namespace Drupal\Core\Update;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;

/**
 * Provides module updates versions handling.
 */
class VersioningUpdateRegistry {

  /**
   * A list of enabled modules.
   *
   * @var string[]
   */
  protected $enabledModules;

  /**
   * The key value storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * A static cache of schema currentVersions per module.
   *
   * @var int[][]
   */
  protected $allVersions = [];

  /**
   * A static cache of installed schema versions per module.
   *
   * @var int[]
   */
  protected $installedVersions = [];

  /**
   * Constructs a new UpdateRegistry.
   *
   * @param string[] $enabled_modules
   *   A list of enabled modules.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $key_value
   *   The key value store.
   */
  public function __construct(array $enabled_modules, KeyValueStoreInterface $key_value) {
    $this->enabledModules = $enabled_modules;
    $this->keyValue = $key_value;
    $this->installedVersions = $this->keyValue->getAll();
  }

  /**
   * Returns an array of available schema versions for a module.
   *
   * @param string $module
   *   A module name.
   *
   * @return int[]
   *   An array of available updates sorted by version. Empty array returned if
   *   no updates available.
   */
  public function getAvailableUpdates(string $module) {
    if (!isset($this->allVersions[$module])) {
      $this->allVersions[$module] = [];

      foreach ($this->enabledModules as $enabled_module) {
        $this->allVersions[$enabled_module] = [];
      }

      // Prepare regular expression to match all possible defined
      // hook_update_N().
      $regexp = '/^(?<module>.+)_update_(?<version>\d+)$/';
      $functions = get_defined_functions();
      // Narrow this down to functions ending with an integer, since all
      // hook_update_N() functions end this way, and there are other
      // possible functions which match '_update_'. We use preg_grep() here
      // since looping through all PHP functions can take significant page
      // execution time and this function is called on every administrative page
      // via system_requirements().
      foreach (preg_grep('/_\d+$/', $functions['user']) as $function) {
        // If this function is a module update function, add it to the list of
        // module updates.
        if (preg_match($regexp, $function, $matches)) {
          $this->allVersions[$matches['module']][] = (int) $matches['version'];
        }
      }
      // Ensure that updates are applied in numerical order.
      array_walk(
        $this->allVersions,
        function (&$module_updates) {
          sort($module_updates, SORT_NUMERIC);
        }
      );
    }

    return empty($this->allVersions[$module]) ? [] : $this->allVersions[$module];
  }

  /**
   * Returns the currently installed schema version for a module.
   *
   * @param string $module
   *   A module name.
   *
   * @return int
   *   The currently installed schema version, or SCHEMA_UNINSTALLED if the
   *   module is not installed.
   */
  public function getInstalledVersion(string $module): int {
    return $this->installedVersions[$module] ?? SCHEMA_UNINSTALLED;
  }

  /**
   * Updates the installed version information for a module.
   *
   * @param string $module
   *   A module name.
   * @param int $version
   *   The new schema version.
   */
  public function setInstalledVersion(string $module, int $version) {
    $this->keyValue->set($module, $version);
    // Update the static cache of module schema versions.
    $this->installedVersions[$module] = $version;
  }

  /**
   * Returns the currently installed schema version for all modules.
   *
   * @return int[]
   *   Array of modules as the keys and values as the currently installed
   *   schema version of corresponding module, or SCHEMA_UNINSTALLED if the
   *   module is not installed.
   */
  public function getAllInstalledVersions(): array {
    return $this->installedVersions;
  }

}
