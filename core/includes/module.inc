<?php

/**
 * @file
 * API for loading and interacting with Drupal modules.
 */

use Drupal\Core\Extension\ExtensionDiscovery;

/**
 * Builds a list of installed themes.
 *
 * @param $type
 *   The type of list to return:
 *   - theme: All installed themes.
 *
 * @return array
 *   An associative array of themes, keyed by name.
 *   For $type 'theme', the array values are objects representing the
 *   respective database row, with the 'info' property already unserialized.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use
 *   \Drupal::service('theme_handler')->listInfo() instead.
 *
 * @see https://www.drupal.org/node/2709919
 * @see \Drupal\Core\Extension\ThemeHandler::listInfo()
 */
function system_list($type) {
  @trigger_error('system_list() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal::service(\'theme_handler\')->listInfo() instead. See https://www.drupal.org/node/2709919', E_USER_DEPRECATED);

  $lists = [
    'theme' => \Drupal::service('theme_handler')->listInfo(),
    'filepaths' => [],
  ];
  foreach ($lists['theme'] as $name => $theme) {
    $lists['filepaths'][] = [
      'type' => 'theme',
      'name' => $name,
      'filepath' => $theme->getPathname(),
    ];
  }
  return $lists[$type];
}

/**
 * Resets all system_list() caches.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. There
 *   is no direct replacement. Call each
 *   \Drupal::service('extension.list.TYPE')->reset() as necessary.
 *
 * @see https://www.drupal.org/node/2709919
 */
function system_list_reset() {
  @trigger_error("system_list_reset() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. There is no direct replacement. Call each \Drupal::service('extension.list.TYPE')->reset() as necessary. See https://www.drupal.org/node/2709919.", E_USER_DEPRECATED);
  \Drupal::service('extension.list.profile')->reset();
  \Drupal::service('extension.list.module')->reset();
  \Drupal::service('extension.list.theme_engine')->reset();
  \Drupal::service('extension.list.theme')->reset();
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
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. There is
 *   no replacement for this function. Use the following sequence of code to
 *   achieve the same functionality:
 *   @code
 *   $path = \Drupal::service("extension.list.$type")->getPath($name);
 *   \Drupal::service('class_loader')->addPsr4('Drupal\\' . $name . '\\', \Drupal::root() . '/' . $path . '/src');
 *   @endcode
 */
function system_register($type, $name, $uri) {
  @trigger_error('system_register() is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. There is no replacement for this function. To achieve the same functionality use this snippet: $path = \Drupal::service("extension.list.$type")->getPath($name); ' . "\\Drupal::service('class_loader')->addPsr4('Drupal\\\\' . \$name . '\\\\', \\Drupal::root() . '/' . \$path . '/src'); See https://www.drupal.org/node/3035275.", E_USER_DEPRECATED);
  \Drupal::service('class_loader')->addPsr4('Drupal\\' . $name . '\\', \Drupal::root() . '/' . dirname($uri) . '/src');
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
 * Returns an array of modules required by core.
 */
function drupal_required_modules() {
  $listing = new ExtensionDiscovery(\Drupal::root());
  $files = $listing->scan('module');
  $required = [];

  // Unless called by the installer, an installation profile is required and
  // must always be loaded.
  if ($profile = \Drupal::installProfile()) {
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
  $extension_config = \Drupal::configFactory()->getEditable('core.extension');
  if ($extension_config->get("module.$module") !== NULL) {
    // Pre-cast the $weight to an integer so that we can save this without using
    // schema. This is a performance improvement for module installation.
    $extension_config
      ->set("module.$module", (int) $weight)
      ->set('module', module_config_sort($extension_config->get('module')))
      ->save(TRUE);

    // Prepare the new module list, sorted by weight, including filenames.
    // @see \Drupal\Core\Extension\ModuleInstaller::install()
    $module_handler = \Drupal::moduleHandler();
    $current_module_filenames = $module_handler->getModuleList();
    $current_modules = array_fill_keys(array_keys($current_module_filenames), 0);
    $current_modules = module_config_sort(array_merge($current_modules, $extension_config->get('module')));
    $module_filenames = [];
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
  $sort = [];
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
