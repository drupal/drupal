<?php

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
function hook_views_ui_display_top_alter(&$build, \Drupal\views_ui\ViewUI $view, $display_id) {
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
function hook_views_ui_display_tab_alter(&$build, \Drupal\views_ui\ViewUI $view, $display_id) {
  $build['custom']['#markup'] = 'This text should always appear';
}

/**
 * @} End of "addtogroup hooks".
 */
