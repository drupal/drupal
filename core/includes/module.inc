<?php

/**
 * @file
 * API for loading and interacting with Drupal modules.
 */

use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ExtensionDiscovery;

/**
 * Builds a list of bootstrap modules and enabled modules and themes.
 *
 * @param $type
 *   The type of list to return:
 *   - module_enabled: All enabled modules.
 *   - bootstrap: All enabled modules required for bootstrap.
 *   - theme: All themes.
 *
 * @return
 *   An associative array of modules or themes, keyed by name. For $type
 *   'bootstrap' and 'module_enabled', the array values equal the keys.
 *   For $type 'theme', the array values are objects representing the
 *   respective database row, with the 'info' property already unserialized.
 *
 * @see list_themes()
 *
 * @todo There are too many layers/levels of caching involved for system_list()
 *   data. Consider to add a \Drupal::config($name, $cache = TRUE) argument to allow
 *   callers like system_list() to force-disable a possible configuration
 *   storage controller cache or some other way to circumvent it/take it over.
 */
function system_list($type) {
  $lists = &drupal_static(__FUNCTION__);
  if ($cached = \Drupal::cache('bootstrap')->get('system_list')) {
    $lists = $cached->data;
  }
  else {
    $lists = array(
      'theme' => array(),
      'filepaths' => array(),
    );
    // Build a list of themes.
    $enabled_themes = (array) \Drupal::config('system.theme')->get('enabled');
    // @todo Themes include all themes, including disabled/uninstalled. This
    //   system.theme.data state will go away entirely as soon as themes have
    //   a proper installation status.
    // @see http://drupal.org/node/1067408
    $theme_data = \Drupal::state()->get('system.theme.data');
    if (empty($theme_data)) {
      // @todo: system_list() may be called from _drupal_bootstrap_code(), in
      // which case system.module is not loaded yet.
      // Prevent a filesystem scan in drupal_load() and include it directly.
      // @see http://drupal.org/node/1067408
      require_once DRUPAL_ROOT . '/core/modules/system/system.module';
      $theme_data = system_rebuild_theme_data();
    }
    foreach ($theme_data as $name => $theme) {
      $theme->status = (int) isset($enabled_themes[$name]);
      $lists['theme'][$name] = $theme;
      // Build a list of filenames so drupal_get_filename can use it.
      if (isset($enabled_themes[$name])) {
        $lists['filepaths'][] = array(
          'type' => 'theme',
          'name' => $name,
          'filepath' => $theme->getPathname(),
        );
      }
    }
    \Drupal::cache('bootstrap')->set('system_list', $lists);
  }
  // To avoid a separate database lookup for the filepath, prime the
  // drupal_get_filename() static cache with all enabled modules and themes.
  foreach ($lists['filepaths'] as $item) {
    system_register($item['type'], $item['name'], $item['filepath']);
  }

  return $lists[$type];
}

/**
 * Resets all system_list() caches.
 */
function system_list_reset() {
  drupal_static_reset('system_list');
  drupal_static_reset('system_rebuild_module_data');
  drupal_static_reset('list_themes');
  \Drupal::cache('bootstrap')->delete('system_list');
  \Drupal::cache()->delete('system_info');

  // Clear the library info cache.
  // Libraries may be provided by all extension types, and may be altered by any
  // other extensions (types) due to the nature of
  // \Drupal\Core\Extension\ModuleHandler::alter() and the fact that profiles
  // are recorded and handled as modules.
  Cache::invalidateTags(array('extension' => TRUE));

  // Remove last known theme data state.
  // This causes system_list() to call system_rebuild_theme_data() on its next
  // invocation. When enabling a module that implements hook_system_info_alter()
  // to inject a new (testing) theme or manipulate an existing theme, then that
  // will cause system_list_reset() to be called, but theme data is not
  // necessarily rebuilt afterwards.
  // @todo Obsolete with proper installation status for themes.
  \Drupal::state()->delete('system.theme.data');
}

/**
 * Registers an extension in runtime registries for execution.
 *
 * @param string $type
 *   The extension type; e.g., 'module' or 'theme'.
 * @param string $name
 *   The internal name of the extension; e.g., 'node'.
 * @param string $uri
 *   The relative URI of the primary extension file; e.g.,
 *   'core/modules/node/node.module'.
 */
function system_register($type, $name, $uri) {
  drupal_get_filename($type, $name, $uri);
  drupal_classloader_register($name, dirname($uri));
}

/**
 * Loads a module's installation hooks.
 *
 * @param $module
 *   The name of the module (without the .module extension).
 *
 * @return
 *   The name of the module's install file, if successful; FALSE otherwise.
 */
function module_load_install($module) {
  // Make sure the installation API is available
  include_once __DIR__ . '/install.inc';

  return module_load_include('install', $module);
}

/**
 * Loads a module include file.
 *
 * Examples:
 * @code
 *   // Load node.admin.inc from the node module.
 *   module_load_include('inc', 'node', 'node.admin');
 *   // Load content_types.inc from the node module.
 *   module_load_include('inc', 'node', 'content_types');
 * @endcode
 *
 * Do not use this function to load an install file, use module_load_install()
 * instead. Do not use this function in a global context since it requires
 * Drupal to be fully bootstrapped, use require_once DRUPAL_ROOT . '/path/file'
 * instead.
 *
 * @param $type
 *   The include file's type (file extension).
 * @param $module
 *   The module to which the include file belongs.
 * @param $name
 *   (optional) The base file name (without the $type extension). If omitted,
 *   $module is used; i.e., resulting in "$module.$type" by default.
 *
 * @return
 *   The name of the included file, if successful; FALSE otherwise.
 *
 * @todo The module_handler service has a loadInclude() method which performs
 *   this same task but only for enabled modules. Figure out a way to move this
 *   functionality entirely into the module_handler while keeping the ability to
 *   load the files of disabled modules.
 */
function module_load_include($type, $module, $name = NULL) {
  if (!isset($name)) {
    $name = $module;
  }

  if (function_exists('drupal_get_path')) {
    $file = DRUPAL_ROOT . '/' . drupal_get_path('module', $module) . "/$name.$type";
    if (is_file($file)) {
      require_once $file;
      return $file;
    }
  }
  return FALSE;
}

/**
 * Installs a given list of modules.
 *
 * @see \Drupal\Core\Extension\ModuleHandlerInterface::install()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0. Use
 *   \Drupal::moduleHandler()->install($module_list, $enable_dependencies = TRUE).
 */
function module_install($module_list, $enable_dependencies = TRUE) {
  return \Drupal::moduleHandler()->install($module_list, $enable_dependencies);
}

/**
 * Installs a given list of modules.
 *
 * @see \Drupal\Core\Extension\ModuleHandlerInterface::module_install()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0. Use
 *   \Drupal::moduleHandler()->uninstall($module_list, $enable_dependencies = TRUE).
 */
function module_uninstall($module_list = array(), $uninstall_dependents = TRUE) {
  return \Drupal::moduleHandler()->uninstall($module_list, $uninstall_dependents);
}

/**
 * @defgroup hooks Hooks
 * @{
 * Allow modules to interact with the Drupal core.
 *
 * Drupal's module system is based on the concept of "hooks". A hook is a PHP
 * function that is named foo_bar(), where "foo" is the name of the module
 * (whose filename is thus foo.module) and "bar" is the name of the hook. Each
 * hook has a defined set of parameters and a specified result type.
 *
 * To extend Drupal, a module need simply implement a hook. When Drupal wishes
 * to allow intervention from modules, it determines which modules implement a
 * hook and calls that hook in all enabled modules that implement it.
 *
 * The available hooks to implement are explained here in the Hooks section of
 * the developer documentation. The string "hook" is used as a placeholder for
 * the module name in the hook definitions. For example, if the module file is
 * called example.module, then hook_help() as implemented by that module would
 * be defined as example_help().
 *
 * The example functions included are not part of the Drupal core, they are
 * just models that you can modify. Only the hooks implemented within modules
 * are executed when running Drupal.
 *
 * @see themeable
 * @see callbacks
 *
 * @} End of "defgroup hooks".
 */

 /**
  * @defgroup callbacks Callbacks
  * @{
  * Callback function signatures.
  *
  * Drupal's API sometimes uses callback functions to allow you to define how
  * some type of processing happens. A callback is a function with a defined
  * signature, which you define in a module. Then you pass the function name as
  * a parameter to a Drupal API function or return it as part of a hook
  * implementation return value, and your function is called at an appropriate
  * time. For instance, when setting up batch processing you might need to
  * provide a callback function for each processing step and/or a callback for
  * when processing is finished; you would do that by defining these functions
  * and passing their names into the batch setup function.
  *
  * Callback function signatures, like hook definitions, are described by
  * creating and documenting dummy functions in a *.api.php file; normally, the
  * dummy callback function's name should start with "callback_", and you should
  * document the parameters and return value and provide a sample function body.
  * Then your API documentation can refer to this callback function in its
  * documentation. A user of your API can usually name their callback function
  * anything they want, although a standard name would be to replace "callback_"
  * with the module name.
  *
  * @see hooks
  * @see themeable
  *
  * @}
  */

/**
 * Returns an array of modules required by core.
 */
function drupal_required_modules() {
  $listing = new ExtensionDiscovery();
  $files = $listing->scan('module');
  $required = array();

  // Unless called by the installer, an installation profile is required and
  // must always be loaded. drupal_get_profile() also returns the installation
  // profile in the installer, but only after it has been selected.
  if ($profile = drupal_get_profile()) {
    $required[] = $profile;
  }

  foreach ($files as $name => $file) {
    $info = \Drupal::service('info_parser')->parse($file->getPathname());
    if (!empty($info) && !empty($info['required']) && $info['required']) {
      $required[] = $name;
    }
  }

  return $required;
}

/**
 * Sets weight of a particular module.
 *
 * The weight of uninstalled modules cannot be changed.
 *
 * @param string $module
 *   The name of the module (without the .module extension).
 * @param int $weight
 *   An integer representing the weight of the module.
 */
function module_set_weight($module, $weight) {
  // Update the module weight in the config file that contains it.
  $module_config = \Drupal::config('system.module');
  if ($module_config->get("enabled.$module") !== NULL) {
    $module_config
      ->set("enabled.$module", $weight)
      ->set('enabled', module_config_sort($module_config->get('enabled')))
      ->save();

    // Prepare the new module list, sorted by weight, including filenames.
    // @see \Drupal\Core\Extension\ModuleHandler::install()
    $module_handler = \Drupal::moduleHandler();
    $current_module_filenames = $module_handler->getModuleList();
    $current_modules = array_fill_keys(array_keys($current_module_filenames), 0);
    $current_modules = module_config_sort(array_merge($current_modules, $module_config->get('enabled')));
    $module_filenames = array();
    foreach ($current_modules as $name => $weight) {
      $module_filenames[$name] = $current_module_filenames[$name];
    }
    // Update the module list in the extension handler.
    $module_handler->setModuleList($module_filenames);
    return;
  }
}

/**
 * Sorts the configured list of enabled modules.
 *
 * The list of enabled modules is expected to be ordered by weight and name.
 * The list is always sorted on write to avoid the overhead on read.
 *
 * @param array $data
 *   An array of module configuration data.
 *
 * @return array
 *   An array of module configuration data sorted by weight and name.
 */
function module_config_sort($data) {
  // PHP array sorting functions such as uasort() do not work with both keys and
  // values at the same time, so we achieve weight and name sorting by computing
  // strings with both information concatenated (weight first, name second) and
  // use that as a regular string sort reference list via array_multisort(),
  // compound of "[sign-as-integer][padded-integer-weight][name]"; e.g., given
  // two modules and weights (spaces added for clarity):
  // - Block with weight -5: 0 0000000000000000005 block
  // - Node  with weight  0: 1 0000000000000000000 node
  $sort = array();
  foreach ($data as $name => $weight) {
    // Prefix negative weights with 0, positive weights with 1.
    // +/- signs cannot be used, since + (ASCII 43) is before - (ASCII 45).
    $prefix = (int) ($weight >= 0);
    // The maximum weight is PHP_INT_MAX, so pad all weights to 19 digits.
    $sort[] = $prefix . sprintf('%019d', abs($weight)) . $name;
  }
  array_multisort($sort, SORT_STRING, $data);
  return $data;
}
