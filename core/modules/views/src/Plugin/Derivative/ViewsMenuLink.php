<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Derivative\ViewsMenuLink.
 */

namespace Drupal\views\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverInterface;
use Drupal\views\Views;

/**
 * Provides menu links for views.
 */
class ViewsMenuLink implements DeriverInterface {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    if (!isset($this->derivatives)) {
      $this->getDerivativeDefinitions($base_plugin_definition);
    }
    if (isset($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // @todo Decide what to do with all the crazy logic in views_menu_alter() in
    // https://drupal.org/node/2107533.
    $links = array();
    $views = Views::getApplicableViews('uses_hook_menu');
    foreach ($views as $data) {
      /** @var \Drupal\views\ViewExecutable $view */
      list($view, $display_id) = $data;
      $result = $view->executeHookMenuLinks($display_id);
      foreach ($result as $link_id => $link) {
        $links[$link_id] = $link + $base_plugin_definition;
      }
    }

    return $links;
  }

}
