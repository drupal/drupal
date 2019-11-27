<?php

/**
 * @file
 * API for the Drupal menu system.
 */

/**
 * @addtogroup menu
 * @{
 */

use Drupal\Component\Render\FormattableMarkup;

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
    $active = new FormattableMarkup('<span class="visually-hidden">@label</span>', ['@label' => t('(active tab)')]);
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
 * @} End of "addtogroup menu".
 */
