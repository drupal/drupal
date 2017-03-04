<?php

namespace Drupal\Core\Update;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;

/**
 * Provides all and missing update implementations.
 *
 * Note: This registry is specific to a type of updates, like 'post_update' as
 * example.
 *
 * It therefore scans for functions named like the type of updates, so it looks
 * like MODULE_UPDATETYPE_NAME() with NAME being a machine name.
 */
class UpdateRegistry {

  /**
   * The used update name.
   *
   * @var string
   */
  protected $updateType = 'post_update';

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The filename of the log file.
   *
   * @var string
   */
  protected $logFilename;

  /**
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
   * Should we respect update functions in tests.
   *
   * @var bool|null
   */
  protected $includeTests = NULL;

  /**
   * The site path.
   *
   * @var string
   */
  protected $sitePath;

  /**
   * Constructs a new UpdateRegistry.
   *
   * @param string $root
   *   The app root.
   * @param string $site_path
   *   The site path.
   * @param string[] $enabled_modules
   *   A list of enabled modules.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $key_value
   *   The key value store.
   * @param bool|null $include_tests
   *   (optional) A flag whether to include tests in the scanning of modules.
   */
  public function __construct($root, $site_path, array $enabled_modules, KeyValueStoreInterface $key_value, $include_tests = NULL) {
    $this->root = $root;
    $this->sitePath = $site_path;
    $this->enabledModules = $enabled_modules;
    $this->keyValue = $key_value;
    $this->includeTests = $include_tests;
  }

  /**
   * Gets all available update functions.
   *
   * @return callable[]
   *   A list of update functions.
   */
  protected function getAvailableUpdateFunctions() {
    $regexp = '/^(?<module>.+)_' . $this->updateType . '_(?<name>.+)$/';
    $functions = get_defined_functions();

    $updates = [];
    foreach (preg_grep('/_' . $this->updateType . '_/', $functions['user']) as $function) {
      // If this function is a module update function, add it to the list of
      // module updates.
      if (preg_match($regexp, $function, $matches)) {
        if (in_array($matches['module'], $this->enabledModules)) {
          $updates[] = $matches['module'] . '_' . $this->updateType . '_' . $matches['name'];
        }
      }
    }

    // Ensure that the update order is deterministic.
    sort($updates);
    return $updates;
  }

  /**
   * Find all update functions that haven't been executed.
   *
   * @return callable[]
   *   A list of update functions.
   */
  public function getPendingUpdateFunctions() {
    // We need a) the list of active modules (we get that from the config
    // bootstrap factory) and b) the path to the modules, we use the extension
    // discovery for that.

    $this->scanExtensionsAndLoadUpdateFiles();

    // First figure out which hook_{$this->updateType}_NAME got executed
    // already.
    $existing_update_functions = $this->keyValue->get('existing_updates', []);

    $available_update_functions = $this->getAvailableUpdateFunctions();
    $not_executed_update_functions = array_diff($available_update_functions, $existing_update_functions);

    return $not_executed_update_functions;
  }

  /**
   * Loads all update files for a given list of extension.
   *
   * @param \Drupal\Core\Extension\Extension[] $module_extensions
   *   The extensions used for loading.
   */
  protected function loadUpdateFiles(array $module_extensions) {
    // Load all the {$this->updateType}.php files.
    foreach ($this->enabledModules as $module) {
      if (isset($module_extensions[$module])) {
        $this->loadUpdateFile($module_extensions[$module]);
      }
    }
  }

  /**
   * Loads the {$this->updateType}.php file for a given extension.
   *
   * @param \Drupal\Core\Extension\Extension $module
   *   The extension of the module to load its file.
   */
  protected function loadUpdateFile(Extension $module) {
    $filename = $this->root . '/' . $module->getPath() . '/' . $module->getName() . ".{$this->updateType}.php";
    if (file_exists($filename)) {
      include_once $filename;
    }
  }

  /**
   * Returns a list of all the pending updates.
   *
   * @return array[]
   *   An associative array keyed by module name which contains all information
   *   about database updates that need to be run, and any updates that are not
   *   going to proceed due to missing requirements.
   *
   *   The subarray for each module can contain the following keys:
   *   - start: The starting update that is to be processed. If this does not
   *       exist then do not process any updates for this module as there are
   *       other requirements that need to be resolved.
   *   - pending: An array of all the pending updates for the module including
   *       the description from source code comment for each update function.
   *       This array is keyed by the update name.
   */
  public function getPendingUpdateInformation() {
    $functions = $this->getPendingUpdateFunctions();

    $ret = [];
    foreach ($functions as $function) {
      list($module, $update) = explode("_{$this->updateType}_", $function);
      // The description for an update comes from its Doxygen.
      $func = new \ReflectionFunction($function);
      $description = trim(str_replace(["\n", '*', '/'], '', $func->getDocComment()), ' ');
      $ret[$module]['pending'][$update] = $description;
      if (!isset($ret[$module]['start'])) {
        $ret[$module]['start'] = $update;
      }
    }
    return $ret;
  }

  /**
   * Registers that update fucntions got executed.
   *
   * @param string[] $function_names
   *   The executed update functions.
   *
   * @return $this
   */
  public function registerInvokedUpdates(array $function_names) {
    $executed_updates = $this->keyValue->get('existing_updates', []);
    $executed_updates = array_merge($executed_updates, $function_names);
    $this->keyValue->set('existing_updates', $executed_updates);

    return $this;
  }

  /**
   * Returns all available updates for a given module.
   *
   * @param string $module_name
   *   The module name.
   *
   * @return callable[]
   *   A list of update functions.
   */
  public function getModuleUpdateFunctions($module_name) {
    $this->scanExtensionsAndLoadUpdateFiles();
    $all_functions = $this->getAvailableUpdateFunctions();

    return array_filter($all_functions, function($function_name) use ($module_name) {
      list($function_module_name, ) = explode("_{$this->updateType}_", $function_name);
      return $function_module_name === $module_name;
    });
  }

  /**
   * Scans all module + profile extensions and load the update files.
   */
  protected function scanExtensionsAndLoadUpdateFiles() {
    // Scan the module list.
    $extension_discovery = new ExtensionDiscovery($this->root, FALSE, [], $this->sitePath);
    $module_extensions = $extension_discovery->scan('module');

    $profile_extensions = $extension_discovery->scan('profile');
    $extensions = array_merge($module_extensions, $profile_extensions);

    $this->loadUpdateFiles($extensions);
  }

  /**
   * Filters out already executed update functions by module.
   *
   * @param string $module
   *   The module name.
   */
  public function filterOutInvokedUpdatesByModule($module) {
    $existing_update_functions = $this->keyValue->get('existing_updates', []);

    $remaining_update_functions = array_filter($existing_update_functions, function($function_name) use ($module) {
      return strpos($function_name, "{$module}_{$this->updateType}_") !== 0;
    });

    $this->keyValue->set('existing_updates', array_values($remaining_update_functions));
  }

}
