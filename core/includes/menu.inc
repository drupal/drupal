<?php

/**
 * @file
 * API for the Drupal menu system.
 */

/**
 * @addtogroup menu
 * @{
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Render\Element;


/**
 * Prepares variables for single local task link templates.
 *
 * Default template: menu-local-task.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element containing:
 *     - #link: A menu link array with 'title', 'url', and (optionally)
 *       'localized_options' keys.
 *     - #active: A boolean indicating whether the local task is active.
 */
function template_preprocess_menu_local_task(&$variables) {
  $link = $variables['element']['#link'];
  $link += array(
    'localized_options' => array(),
  );
  $link_text = $link['title'];

  if (!empty($variables['element']['#active'])) {
    $variables['is_active'] = TRUE;

    // Add text to indicate active tab for non-visual users.
    $active = SafeMarkup::format('<span class="visually-hidden">@label</span>', array('@label' => t('(active tab)')));
    $link_text = t('@local-task-title@active', array('@local-task-title' => $link_text, '@active' => $active));
  }
  else {
    // @todo Remove this once https://www.drupal.org/node/2338081 is fixed.
    $link_text = SafeMarkup::checkPlain($link_text);
  }

  $link['localized_options']['set_active_class'] = TRUE;

  $variables['link'] = array(
    '#type' => 'link',
    '#title' => $link_text,
    '#url' => $link['url'],
    '#options' => $link['localized_options'],
  );
}

/**
 * Prepares variables for single local action link templates.
 *
 * Default template: menu-local-action.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element containing:
 *     - #link: A menu link array with 'title', 'url', and (optionally)
 *       'localized_options' keys.
 */
function template_preprocess_menu_local_action(&$variables) {
  $link = $variables['element']['#link'];
  $link += array(
    'localized_options' => array(),
  );
  $link['localized_options']['attributes']['class'][] = 'button';
  $link['localized_options']['attributes']['class'][] = 'button-action';
  $link['localized_options']['set_active_class'] = TRUE;

  $variables['link'] = array(
    '#type' => 'link',
    '#title' => $link['title'],
    '#options' => $link['localized_options'],
    '#url' => $link['url'],
  );
}

/**
 * Returns an array containing the names of system-defined (default) menus.
 */
function menu_list_system_menus() {
  return array(
    'tools' => 'Tools',
    'admin' => 'Administration',
    'account' => 'User account menu',
    'main' => 'Main navigation',
    'footer' => 'Footer menu',
  );
}

/**
 * Collects the local tasks (tabs), action links, and the root path.
 *
 * @param int $level
 *   The level of tasks you ask for. Primary tasks are 0, secondary are 1.
 *
 * @return array
 *   An array containing
 *   - tabs: Local tasks for the requested level.
 *   - actions: Action links for the requested level.
 *   - root_path: The router path for the current page. If the current page is
 *     a default local task, then this corresponds to the parent tab.
 *
 * @see hook_menu_local_tasks()
 * @see hook_menu_local_tasks_alter()
 */
function menu_local_tasks($level = 0) {
  $data = &drupal_static(__FUNCTION__);
  $root_path = &drupal_static(__FUNCTION__ . ':root_path', '');
  $empty = array(
    'tabs' => array(),
    'actions' => array(),
    'root_path' => &$root_path,
  );

  if (!isset($data)) {
    // Look for route-based tabs.
    $data['tabs'] = array();
    $data['actions'] = array();

    $route_name = \Drupal::routeMatch()->getRouteName();
    if (!\Drupal::request()->attributes->has('exception') && !empty($route_name)) {
      $manager = \Drupal::service('plugin.manager.menu.local_task');
      $local_tasks = $manager->getTasksBuild($route_name);
      foreach ($local_tasks as $level => $items) {
        $data['tabs'][$level] = empty($data['tabs'][$level]) ? $items : array_merge($data['tabs'][$level], $items);
      }
    }

    // Allow modules to dynamically add further tasks.
    $module_handler = \Drupal::moduleHandler();
    foreach ($module_handler->getImplementations('menu_local_tasks') as $module) {
      $function = $module . '_menu_local_tasks';
      $function($data, $route_name);
    }
    // Allow modules to alter local tasks.
    $module_handler->alter('menu_local_tasks', $data, $route_name);
  }

  if (isset($data['tabs'][$level])) {
    return array(
      'tabs' => $data['tabs'][$level],
      'actions' => $data['actions'],
      'root_path' => $root_path,
    );
  }
  elseif (!empty($data['actions'])) {
    return array('actions' => $data['actions']) + $empty;
  }
  return $empty;
}

/**
 * Returns the rendered local tasks at the top level.
 */
function menu_primary_local_tasks() {
  $links = menu_local_tasks(0);
  // Do not display single tabs.
  return count(Element::getVisibleChildren($links['tabs'])) > 1 ? $links['tabs'] : '';
}

/**
 * Returns the rendered local tasks at the second level.
 */
function menu_secondary_local_tasks() {
  $links = menu_local_tasks(1);
  // Do not display single tabs.
  return count(Element::getVisibleChildren($links['tabs'])) > 1 ? $links['tabs'] : '';
}

/**
 * Returns the rendered local actions at the current level.
 */
function menu_get_local_actions() {
  $links = menu_local_tasks();
  $route_name = Drupal::routeMatch()->getRouteName();
  $manager = \Drupal::service('plugin.manager.menu.local_action');
  return $manager->getActionsForRoute($route_name) + $links['actions'];
}

/**
 * Returns the router path, or the path for a default local task's parent.
 */
function menu_tab_root_path() {
  $links = menu_local_tasks();
  return $links['root_path'];
}

/**
 * Returns a renderable element for the primary and secondary tabs.
 */
function menu_local_tabs() {
  $build = array(
    '#theme' => 'menu_local_tasks',
    '#primary' => menu_primary_local_tasks(),
    '#secondary' => menu_secondary_local_tasks(),
  );
  return !empty($build['#primary']) || !empty($build['#secondary']) ? $build : array();
}

/**
 * Clears all cached menu data.
 *
 * This should be called any time broad changes
 * might have been made to the router items or menu links.
 */
function menu_cache_clear_all() {
  \Drupal::cache('menu')->invalidateAll();
}

/**
 * @} End of "addtogroup menu".
 */
