<?php

namespace Drupal\Core\Update;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;

/**
 * Provides module updates versions handling.
 */
class UpdateHookRegistry {

  /**
   * Indicates that a module has not been installed yet.
   */
  public const SCHEMA_UNINSTALLED = -1;

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
   * Stores schema versions of the modules based on their defined hook_update_N
   * implementations.
   * Example:
   * ```
   * [
   *   'example_module' => [
   *     8000,
   *     8001,
   *     8002
   *   ]
   * ]
   * ```
   *
   * @var int[][]
   * @see \Drupal\Core\Update\UpdateHookRegistry::getAvailableUpdates()
   */
  protected $allAvailableSchemaVersions = [];

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
  public function getAvailableUpdates(string $module): array {
    if (!isset($this->allAvailableSchemaVersions[$module])) {
      $this->allAvailableSchemaVersions[$module] = [];

      foreach ($this->enabledModules as $enabled_module) {
        $this->allAvailableSchemaVersions[$enabled_module] = [];
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
          $this->allAvailableSchemaVersions[$matches['module']][] = (int) $matches['version'];
        }
      }
      // Ensure that updates are applied in numerical order.
      array_walk(
        $this->allAvailableSchemaVersions,
        static function (&$module_updates) {
          sort($module_updates, SORT_NUMERIC);
        }
      );
    }

    return $this->allAvailableSchemaVersions[$module];
  }

  /**
   * Returns the currently installed schema version for a module.
   *
   * @param string $module
   *   A module name.
   *
   * @return int
   *   The currently installed schema version, or self::SCHEMA_UNINSTALLED if the
   *   module is not installed.
   */
  public function getInstalledVersion(string $module): int {
    return $this->keyValue->get($module, self::SCHEMA_UNINSTALLED);
  }

  /**
   * Updates the installed version information for a module.
   *
   * @param string $module
   *   A module name.
   * @param int $version
   *   The new schema version.
   *
   * @return self
   *   Returns self to support chained method calls.
   */
  public function setInstalledVersion(string $module, int $version): self {
    $this->keyValue->set($module, $version);
    return $this;
  }

  /**
   * Deletes the installed version information for the module.
   *
   * @param string $module
   *   The module name to delete.
   */
  public function deleteInstalledVersion(string $module): void {
    $this->keyValue->delete($module);
  }

  /**
   * Returns the currently installed schema version for all modules.
   *
   * @return int[]
   *   Array of modules as the keys and values as the currently installed
   *   schema version of corresponding module, or self::SCHEMA_UNINSTALLED if the
   *   module is not installed.
   */
  public function getAllInstalledVersions(): array {
    return $this->keyValue->getAll();
  }

}
