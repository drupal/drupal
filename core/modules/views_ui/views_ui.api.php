<?php

/**
 * @file
 */

use Drupal\views_ui\ViewUI;

/**
 * @file
 * Describes hooks provided by the Views UI module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the top of the display for the Views UI.
 *
 * This hook can be implemented by themes.
 *
 * @param array[] $build
 *   Render array for the display top.
 * @param \Drupal\views_ui\ViewUI $view
 *   The view being edited.
 * @param string $display_id
 *   The display ID.
 *
 * @todo Until https://www.drupal.org/project/drupal/issues/3087455 is resolved,
 *   use this hook or hook_views_ui_display_tab_alter() instead of
 *   hook_form_view_edit_form_alter().
 *
 * @see \Drupal\views_ui\ViewUI::renderDisplayTop()
 */
function hook_views_ui_display_top_alter(&$build, ViewUI $view, $display_id) {
  $build['custom']['#markup'] = 'This text should always appear';
}

/**
 * Alter the renderable array representing the edit page for one display.
 *
 * This hook can be implemented by themes.
 *
 * @param array[] $build
 *   Render array for the tab contents.
 * @param \Drupal\views_ui\ViewUI $view
 *   The view being edited.
 * @param string $display_id
 *   The display ID.
 *
 * @todo Until https://www.drupal.org/project/drupal/issues/3087455 is resolved,
 *   use this hook or hook_views_ui_display_tab_alter() instead of
 *   hook_form_view_edit_form_alter().
 *
 * @see \Drupal\views_ui\ViewEditForm::getDisplayTab()
 */
function hook_views_ui_display_tab_alter(&$build, ViewUI $view, $display_id) {
  $build['custom']['#markup'] = 'This text should always appear';
}

/**
 * Alter the links displayed at the top of the view edit form.
 *
 * @param array $links
 *   A renderable array of links which will be displayed at the top of the
 *   view edit form. Each entry will be in a form suitable for
 *   '#theme' => 'links'.
 * @param \Drupal\views\ViewExecutable $view
 *   The view object being edited.
 * @param string $display_id
 *   The ID of the display being edited, e.g. 'default' or 'page_1'.
 *
 * @see \Drupal\views_ui\ViewUI::renderDisplayTop()
 */
function hook_views_ui_display_top_links_alter(array &$links, ViewExecutable $view, $display_id) {
  // Put the export link first in the list.
  if (isset($links['export'])) {
    $links = ['export' => $links['export']] + $links;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
