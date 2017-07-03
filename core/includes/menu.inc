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
  $link += [
    'localized_options' => [],
  ];
  $link_text = $link['title'];

  if (!empty($variables['element']['#active'])) {
    $variables['is_active'] = TRUE;

    // Add text to indicate active tab for non-visual users.
    $active = SafeMarkup::format('<span class="visually-hidden">@label</span>', ['@label' => t('(active tab)')]);
    $link_text = t('@local-task-title@active', ['@local-task-title' => $link_text, '@active' => $active]);
  }

  $link['localized_options']['set_active_class'] = TRUE;

  $variables['link'] = [
    '#type' => 'link',
    '#title' => $link_text,
    '#url' => $link['url'],
    '#options' => $link['localized_options'],
  ];
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
  $link += [
    'localized_options' => [],
  ];
  $link['localized_options']['attributes']['class'][] = 'button';
  $link['localized_options']['attributes']['class'][] = 'button-action';
  $link['localized_options']['set_active_class'] = TRUE;

  $variables['link'] = [
    '#type' => 'link',
    '#title' => $link['title'],
    '#options' => $link['localized_options'],
    '#url' => $link['url'],
  ];
}

/**
 * Returns an array containing the names of system-defined (default) menus.
 */
function menu_list_system_menus() {
  return [
    'tools' => 'Tools',
    'admin' => 'Administration',
    'account' => 'User account menu',
    'main' => 'Main navigation',
    'footer' => 'Footer menu',
  ];
}

/**
 * Collects the local tasks (tabs) for the current route.
 *
 * @param int $level
 *   The level of tasks you ask for. Primary tasks are 0, secondary are 1.
 *
 * @return array
 *   An array containing
 *   - tabs: Local tasks for the requested level.
 *   - route_name: The route name for the current page used to collect the local
 *     tasks.
 *
 * @see hook_menu_local_tasks_alter()
 * @see https://www.drupal.org/node/2544940
 *
 * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
 */
function menu_local_tasks($level = 0) {
  /** @var \Drupal\Core\Menu\LocalTaskManagerInterface $manager */
  $manager = \Drupal::service('plugin.manager.menu.local_task');
  return $manager->getLocalTasks(\Drupal::routeMatch()->getRouteName(), $level);
}

/**
 * Returns the rendered local tasks at the top level.
 *
 * @see https://www.drupal.org/node/2874695
 *
 * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
 */
function menu_primary_local_tasks() {
  /** @var \Drupal\Core\Menu\LocalTaskManagerInterface $manager */
  $manager = \Drupal::service('plugin.manager.menu.local_task');
  $links = $manager->getLocalTasks(\Drupal::routeMatch()->getRouteName(), 0);
  // Do not display single tabs.
  return count(Element::getVisibleChildren($links['tabs'])) > 1 ? $links['tabs'] : '';
}

/**
 * Returns the rendered local tasks at the second level.
 *
 * @see https://www.drupal.org/node/2874695
 *
 * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
 */
function menu_secondary_local_tasks() {
  /** @var \Drupal\Core\Menu\LocalTaskManagerInterface $manager */
  $manager = \Drupal::service('plugin.manager.menu.local_task');
  $links = $manager->getLocalTasks(\Drupal::routeMatch()->getRouteName(), 1);
  // Do not display single tabs.
  return count(Element::getVisibleChildren($links['tabs'])) > 1 ? $links['tabs'] : '';
}

/**
 * Returns a renderable element for the primary and secondary tabs.
 */
function menu_local_tabs() {
  $build = [
    '#theme' => 'menu_local_tasks',
    '#primary' => menu_primary_local_tasks(),
    '#secondary' => menu_secondary_local_tasks(),
  ];
  return !empty($build['#primary']) || !empty($build['#secondary']) ? $build : [];
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
