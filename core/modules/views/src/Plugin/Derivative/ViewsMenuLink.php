<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Derivative\ViewsMenuLink.
 */

namespace Drupal\views\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverInterface;
use Drupal\views\Views;

/**
 * Provides menu links for Views.
 *
 * @see \Drupal\views\Plugin\Menu\ViewsMenuLink
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
    $links = array();
    $views = Views::getApplicableViews('uses_menu_links');
    foreach ($views as $data) {
      /** @var \Drupal\views\ViewExecutable $view */
      list($view, $display_id) = $data;
      if ($result = $view->getMenuLinks($display_id)) {
        foreach ($result as $link_id => $link) {
          $links[$link_id] = $link + $base_plugin_definition;
        }
      }
    }

    return $links;
  }

}
