<?php

/**
 * @file
 * Admin page callbacks for the help module.
 */

/**
 * Page callback: Prints a page listing general help for a module.
 *
 * @param $name
 *   A module name to display a help page for.
 *
 * @see help_menu()
 */
function help_page($name) {
  $output = '';
  if (module_hook($name, 'help')) {
    $info = system_get_info('module');
    drupal_set_title($info[$name]['name']);

    $temp = module_invoke($name, 'help', "admin/help#$name", drupal_help_arg());
    if (empty($temp)) {
      $output .= t("No help is available for module %module.", array('%module' => $info[$name]['name']));
    }
    else {
      $output .= $temp;
    }

    // Only print list of administration pages if the module in question has
    // any such pages associated to it.
    $admin_tasks = system_get_module_admin_tasks($name, $info[$name]);
    if (!empty($admin_tasks)) {
      $links = array();
      foreach ($admin_tasks as $task) {
        $links[] = l($task['title'], $task['link_path'], $task['localized_options']);
      }
      $output .= theme('item_list', array('items' => $links, 'title' => t('@module administration pages', array('@module' => $info[$name]['name']))));
    }
  }
  return $output;
}
