<?php

/**
 * @file
 *
 * Registry magic. In a separate file to minimize unnecessary code loading.
 */

/**
 * Implements (via delegation) hook_registry_files_alter().
 *
 * Alter the registry of files to automagically include all classes in
 * class-based plugins.
 */
function _ctools_registry_files_alter(&$files, $indexed_modules) {
  ctools_include('plugins');

  // Remap the passed indexed modules into a useful format.
  $modules = array();
  foreach ($indexed_modules as $module_object) {
    $modules[$module_object->name] = $module_object;
  }

  $all_type_info = ctools_plugin_get_plugin_type_info(TRUE);
  foreach ($all_type_info as $module => $plugin_types) {
    foreach ($plugin_types as $plugin_type_name => $plugin_type_info) {
      if (empty($plugin_type_info['classes'])) {
        // This plugin type does not use classes, so skip it.
        continue;
      }

      // Retrieve the full list of plugins of this type.
      $plugins = ctools_get_plugins($module, $plugin_type_name);
      foreach ($plugins as $plugin_name => $plugin_definition) {
        foreach ($plugin_type_info['classes'] as $class_key) {
          if (empty($plugin_definition[$class_key])) {
            // Plugin doesn't provide a class for this class key, so skip it.
            continue;
          }

          if (is_string($plugin_definition[$class_key])) {
            // Plugin definition uses the shorthand for defining a class name
            // and location; look for the containing file by following naming
            // convention.
            $path = $plugin_definition['path'] . '/' . $plugin_definition[$class_key] . '.class.php';
          }
          else {
            // Plugin uses the verbose definition to indicate where its class
            // files are.
            $class = $plugin_definition[$class_key]['class'];
            // Use the filename if it's explicitly set, else follow the naming
            // conventions.
            $filename = isset($plugin_definition[$class_key]['file']) ? $plugin_definition[$class_key]['file'] : $class . '.class.php';
            $base_path = isset($plugin_definition[$class_key]['path']) ? $plugin_definition[$class_key]['path'] : $plugin_definition['path'];
            $path = "$base_path/$filename";
          }

          if (file_exists($path)) {
            // If the file exists, add it to the files for registry building.
            $files[$path] = array('module' => $plugin_definition['module'], 'weight' => $modules[$plugin_definition['module']]->weight);
          }
          else {
            // Else, watchdog that we've got some erroneous plugin info.
            $args = array(
              '@plugin' => $plugin_definition['name'],
              '@owner' => $module,
              '@type' => $plugin_type_name,
              '@file' => $path,
              '@class' => $class_key,
            );
            watchdog('ctools', 'Plugin @plugin of plugin type @owner:@type points to nonexistent file @file for class handler @class.', $args);
          }
        }
      }
    }
  }
}
