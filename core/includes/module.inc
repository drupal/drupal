<?php

/**
 * @file
 * API for loading and interacting with Drupal modules.
 */

use Drupal\Core\Extension\ExtensionDiscovery;

/**
 * Loads a module's installation hooks.
 *
 * @param $module
 *   The name of the module (without the .module extension).
 *
 * @return
 *   The name of the module's install file, if successful; FALSE otherwise.
 *
 * @deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Use
 *   \Drupal::moduleHandler()->loadInclude($module, 'install') instead. Note,
 *   the replacement no longer allows including code from uninstalled modules.
 *
 * @see https://www.drupal.org/node/3220952
 */
function module_load_install($module) {
  @trigger_error('module_load_install() is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Instead, you should use \Drupal::moduleHandler()->loadInclude($module, \'install\'). Note, the replacement no longer allows including code from uninstalled modules. See https://www.drupal.org/project/drupal/issues/2010380', E_USER_DEPRECATED);
  // Make sure the installation API is available
  include_once __DIR__ . '/install.inc';

  if (\Drupal::hasService('extension.list.module')) {
    /** @var \Drupal\Core\Extension\ModuleExtensionList $module_list */
    $module_list = \Drupal::service('extension.list.module');
    $file = DRUPAL_ROOT . '/' . $module_list->getPath($module) . "/$module.install";
    if (is_file($file)) {
      require_once $file;
      return $file;
    }
  }
  return FALSE;
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
 * @return string|false
 *   The name of the included file, if successful; FALSE otherwise.
 *
 * @deprecated in drupal:9.4.0 and is removed from drupal:11.0.0.
 *   Use \Drupal::moduleHandler()->loadInclude($module, $type, $name = NULL).
 *   Note that including code from uninstalled extensions is no longer
 *   supported.
 *
 * @see https://www.drupal.org/node/2948698
 */
function module_load_include($type, $module, $name = NULL) {
  @trigger_error("module_load_include() is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. Instead, you should use \Drupal::moduleHandler()->loadInclude(). Note that including code from uninstalled extensions is no longer supported. See https://www.drupal.org/project/drupal/issues/697946", E_USER_DEPRECATED);
  if (!isset($name)) {
    $name = $module;
  }

  if (\Drupal::hasService('extension.list.module')) {
    /** @var \Drupal\Core\Extension\ModuleExtensionList $module_list */
    $module_list = \Drupal::service('extension.list.module');
    $file = DRUPAL_ROOT . '/' . $module_list->getPath($module) . "/$name.$type";
    if (is_file($file)) {
      require_once $file;
      return $file;
    }
  }
  return FALSE;
}

/**
 * Returns an array of modules required by core.
 *
 * @deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. There's no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3262811
 */
function drupal_required_modules() {
  @trigger_error(__FUNCTION__ . "() is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. There's no replacement. See https://www.drupal.org/node/3262811", E_USER_DEPRECATED);
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
