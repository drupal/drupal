<?php

/**
 * @file
 * Contains \Drupal\Core\Extension\UpdateModuleHandler.
 */

namespace Drupal\Core\Extension;

use Drupal\Core\Config\ConfigException;
use Drupal\Core\Config\FileStorage;

/**
 * Deals with module enables and throws exception if hooks fired during updates.
 *
 * This is necessary for a reliable and testable update environment.
 */
class UpdateModuleHandler extends ModuleHandler {

  /**
   * {@inheritdoc}
   */
  public function getImplementations($hook) {
    if (substr($hook, -6) === '_alter') {
      return array();
    }
    // _theme() is called during updates and fires hooks, so whitelist the
    // system module.
    if (substr($hook, 0, 6) == 'theme_') {
      return array('system');
    }
    switch ($hook) {
      // hook_requirements is necessary for updates to work.
      case 'requirements':
      // Allow logging.
      case 'watchdog':
        return parent::getImplementations($hook);

      // Forms and pages do not render without the basic elements defined in
      // system_element_info().
      case 'element_info':
      // Forms do not render without the basic elements in
      // drupal_common_theme() called from system_theme().
      case 'theme':
      // Allow access to stream wrappers.
      case 'stream_wrappers':
        return array('system');

      // Those are needed by user_access() to check access on update.php.
      case 'entity_type_build':
      case 'entity_load':
      case 'user_role_load':
        return array();

      // t() in system_stream_wrappers() needs this. Other schema calls aren't
      // supported.
      case 'schema':
        return array('locale');

      default:
        throw new \LogicException("Invoking hooks $hook is not supported during updates");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function install(array $module_list, $enable_dependencies = TRUE) {
    $schema_store = \Drupal::keyValue('system.schema');
    $old_schema = array();
    foreach ($module_list as $module) {
      // Check for initial schema and install it.
      module_load_install($module);
      $function = $module . '_schema_0';
      if (function_exists($function)) {
        $schema = $function();
        foreach ($schema as $table => $spec) {
          db_create_table($table, $spec);
        }
      }

      // Enable the module with a weight of 0.
      $module_config = \Drupal::config('system.module');
      $module_config
        ->set("enabled.$module", 0)
        ->set('enabled', module_config_sort($module_config->get('enabled')))
        ->save();

      $current_schema = $schema_store->get($module);
      // Set the schema version if the module was not just disabled before.
      if ($current_schema === NULL || $current_schema === SCHEMA_UNINSTALLED) {
        // Change the schema version to the given value (defaults to 0), so any
        // module updates since the module's inception are executed in a core
        // upgrade.
        $schema_store->set($module, 0);
        $old_schema[$module] = SCHEMA_UNINSTALLED;
      }
      else {
        $old_schema[$module] = $current_schema;
      }

      // Copy the default configuration of the module into the active storage.
      // The default configuration is not altered in any way, and since the module
      // is just being installed, none of its configuration can exist already, so
      // this is a plain copy operation from one storage to another.
      $module_config_path = drupal_get_path('module', $module) . '/config';
      if (is_dir($module_config_path)) {
        $module_filestorage = new FileStorage($module_config_path);
        $config_storage = \Drupal::service('config.storage');
        foreach ($module_filestorage->listAll() as $config_name) {
          // If this file already exists, something in the upgrade path went
          // completely wrong and we want to know.
          if ($config_storage->exists($config_name)) {
            throw new ConfigException(format_string('Default configuration file @name of @module module unexpectedly exists already before the module was installed.', array(
              '@module' => $module,
              '@name' => $config_name,
            )));
          }
          $config_storage->write($config_name, $module_filestorage->read($config_name));
        }
      }

      // system_list_reset() is in module.inc but that would only be available
      // once the variable bootstrap is done.
      require_once DRUPAL_ROOT . '/core/includes/module.inc';
      system_list_reset();
      $this->moduleList[$module] = drupal_get_filename('module', $module);
      $this->load($module);
      drupal_classloader_register($module, dirname($this->moduleList[$module]));
      // @todo Figure out what to do about hook_install() and hook_enable().
    }
    return $old_schema;
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(array $module_list, $uninstall_dependents = TRUE) {
    throw new \LogicException('Uninstalling modules is not supported during updates');
  }

}
