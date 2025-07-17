<?php

namespace Drupal\views\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views.
 */
class ViewsThemeHooks {

  /**
   * Implements hook_preprocess_HOOK().
   *
   * Allows view-based node templates if called from a view.
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(&$variables): void {
    // The 'view' attribute of the node is added in
    // \Drupal\views\Plugin\views\row\EntityRow::preRender().
    if (!empty($variables['node']->view) && $variables['node']->view->storage->id()) {
      $variables['view'] = $variables['node']->view;

      // The view variable is deprecated.
      $variables['deprecations']['view'] = "'view' is deprecated in drupal:11.1.0 and is removed in drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3459903";

      // If a node is being rendered in a view, and the view does not have a
      // path, prevent drupal from accidentally setting the $page variable.
      if (
        !empty($variables['view']->current_display)
        && $variables['page']
        && $variables['view_mode'] == 'full'
        && !$variables['view']->display_handler->hasPath()
      ) {
        $variables['page'] = FALSE;
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK().
   *
   * Allows view-based comment templates if called from a view.
   */
  #[Hook('preprocess_comment')]
  public function preprocessComment(&$variables): void {
    // The view data is added to the comment in
    // \Drupal\views\Plugin\views\row\EntityRow::preRender().
    if (!empty($variables['comment']->view) && $variables['comment']->view->storage->id()) {
      $variables['view'] = $variables['comment']->view;
    }
  }

}
