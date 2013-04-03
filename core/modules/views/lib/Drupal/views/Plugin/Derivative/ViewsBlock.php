<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Derivative\ViewsBlock.
 */

namespace Drupal\views\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Provides block plugin definitions for all Views block displays.
 *
 * @see \Drupal\views\Plugin\block\block\ViewsBlock
 */
class ViewsBlock implements DerivativeInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinition().
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinitions().
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    // Check all Views for block displays.
    foreach (views_get_all_views() as $view) {
      // Do not return results for disabled views.
      if (!$view->status()) {
        continue;
      }
      $executable = $view->get('executable');
      $executable->initDisplay();
      foreach ($executable->displayHandlers as $display) {
        // Add a block plugin definition for each block display.
        if (isset($display) && !empty($display->definition['uses_hook_block'])) {
          $delta = $view->id() . '-' . $display->display['id'];
          $desc = $display->getOption('block_description');

          if (empty($desc)) {
            if ($display->display['display_title'] == $display->definition['title']) {
              $desc = t('View: !view', array('!view' => $view->label()));
            }
            else {
              $desc = t('View: !view: !display', array('!view' => $view->label(), '!display' => $display->display['display_title']));
            }
          }
          $this->derivatives[$delta] = array(
            'admin_label' => $desc,
            'cache' => $display->getCacheType()
          );
          $this->derivatives[$delta] += $base_plugin_definition;
        }
      }
    }
    return $this->derivatives;
  }

}
