<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Derivative\ViewsExposedFilterBlock.
 */

namespace Drupal\views\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Provides block plugin definitions for all Views exposed filters.
 *
 * @see \Drupal\views\Plugin\block\block\ViewsExposedFilterBlock
 */
class ViewsExposedFilterBlock implements DerivativeInterface {

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
    // Check all Views for displays with an exposed filter block.
    foreach (views_get_all_views() as $view) {
      // Do not return results for disabled views.
      if (!$view->status()) {
        continue;
      }
      $executable = $view->get('executable');
      $executable->initDisplay();
      foreach ($executable->displayHandlers as $display) {
        if (isset($display) && $display->getOption('exposed_block')) {
          // Add a block definition for the block.
          if ($display->usesExposedFormInBlock()) {
            $delta = $view->id() . '-' . $display->display['id'];
            $desc = t('Exposed form: @view-@display_id', array('@view' => $view->id(), '@display_id' => $display->display['id']));
            $this->derivatives[$delta] = array(
              'admin_label' => $desc,
              'cache' => DRUPAL_NO_CACHE,
            );
            $this->derivatives[$delta] += $base_plugin_definition;
          }
        }
      }
    }
    return $this->derivatives;
  }

}
