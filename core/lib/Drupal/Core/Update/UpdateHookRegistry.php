<?php

namespace Drupal\Core\Update;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
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
   * Regular expression to match all possible defined hook_update_N().
   */
  private const FUNC_NAME_REGEXP = '/^(?<module>.+)_update_(?<version>\d+)$/';

  /**
   * A list of enabled modules.
   *
   * @var string[]
   */
  protected $enabledModules;

  /**
   * The system.schema key value storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * The core.equivalent_updates key value storage.
   *
   * The key value keys are modules and the value is an array of equivalent
   * updates with the following shape:
   * - The array keys are the equivalent future update numbers.
   * - The value is an array containing two keys:
   *   - 'ran_update': The update that registered the future update as an
   *     equivalent.
   *   - 'future_version_string': The version that provides the future update.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   * @see module.api.php
   */
  protected KeyValueStoreInterface $equivalentUpdates;

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
   * Constructs a new UpdateHookRegistry.
   *
   * @param array $module_list
   *   An associative array whose keys are the names of installed modules.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key value factory.
   */
  public function __construct(
    array $module_list,
    KeyValueFactoryInterface $key_value_factory,
  ) {
    $this->enabledModules = array_keys($module_list);
    $this->keyValue = $key_value_factory->get('system.schema');
    $this->equivalentUpdates = $key_value_factory->get('core.equivalent_updates');
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
        if (preg_match(self::FUNC_NAME_REGEXP, $function, $matches)) {
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
    $this->deleteEquivalentUpdate($module, $version);
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
    $this->equivalentUpdates->delete($module);
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

  /**
   * Marks a future update as equivalent to the current update running.
   *
   * Updates can be marked as equivalent when they are backported to a
   * previous, but still supported, major version. For example:
   * - A 2.x hook_update_N() would be added as normal, for example:
   *   MODULE_update_2005().
   * - When that same update is backported to 1.x, it is given its own update
   *   number, for example: MODULE_update_1040(). In this update, a call to
   *   @code
   *   \Drupal::service('update.update_hook_registry')->markFutureUpdateEquivalent(2005, '2.10')
   *   @endcode
   *   is added to ensure that a site that has run this update does not run
   *   MODULE_update_2005().
   *
   * @param int $future_update_number
   *   The future update number.
   * @param string $future_version_string
   *   The version that contains the future update.
   */
  public function markFutureUpdateEquivalent(int $future_update_number, string $future_version_string): void {
    [$module, $ran_update_number] = $this->determineModuleAndVersion();

    if ($ran_update_number > $future_update_number) {
      throw new \LogicException(sprintf(
        'Cannot mark the update %d as an equivalent since it is less than the current update %d for the %s module ',
        $future_update_number, $ran_update_number, $module
      ));
    }

    $data = $this->equivalentUpdates->get($module, []);
    // It does not matter if $data[$future_update_number] is already set. If two
    // updates are causing the same update to be marked as equivalent then the
    // latest information is the correct information to use.
    $data[$future_update_number] = [
      'ran_update' => $ran_update_number,
      'future_version_string' => $future_version_string,
    ];
    $this->equivalentUpdates->set($module, $data);
  }

  /**
   * Gets the EquivalentUpdate object for an update.
   *
   * @param string|null $module
   *   The module providing the update. If this is NULL the update to check will
   *   be determined from the backtrace.
   * @param int|null $version
   *   The update to check. If this is NULL the update to check will
   *   be determined from the backtrace.
   *
   * @return \Drupal\Core\Update\EquivalentUpdate|null
   *   A value object with the equivalent update information or NULL if the
   *   update does not have an equivalent update.
   */
  public function getEquivalentUpdate(?string $module = NULL, ?int $version = NULL): ?EquivalentUpdate {
    if ($module === NULL || $version === NULL) {
      [$module, $version] = $this->determineModuleAndVersion();
    }
    $data = $this->equivalentUpdates->get($module, []);
    if (isset($data[$version]['ran_update'])) {
      return new EquivalentUpdate(
        $module,
        $version,
        $data[$version]['ran_update'],
        $data[$version]['future_version_string'],
      );
    }
    return NULL;
  }

  /**
   * Determines the module and update number from the stack trace.
   *
   * @return array<string, int>
   *   An array with two values. The first value is the module name and the
   *   second value is the update number.
   */
  private function determineModuleAndVersion(): array {
    $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

    for ($i = 0; $i < count($stack); $i++) {
      if (preg_match(self::FUNC_NAME_REGEXP, $stack[$i]['function'], $matches)) {
        return [$matches['module'], $matches['version']];
      }
    }

    throw new \BadMethodCallException(__METHOD__ . ' must be called from a hook_update_N() function');
  }

  /**
   * Removes an equivalent update.
   *
   * @param string $module
   *   The module providing the update.
   * @param int $version
   *   The equivalent update to remove.
   *
   * @return bool
   *   TRUE if an equivalent update was removed, or FALSE if it was not.
   */
  protected function deleteEquivalentUpdate(string $module, int $version): bool {
    $data = $this->equivalentUpdates->get($module, []);
    if (isset($data[$version])) {
      unset($data[$version]);
      if (empty($data)) {
        $this->equivalentUpdates->delete($module);
      }
      else {
        $this->equivalentUpdates->set($module, $data);
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Returns the equivalent update information for all modules.
   *
   * @return array<string, array<int, array{ran_update:int, future_version_string: string}>>
   *   Array of modules as the keys and values as arrays of equivalent update
   *   information.
   */
  public function getAllEquivalentUpdates(): array {
    return $this->equivalentUpdates->getAll();
  }

}
