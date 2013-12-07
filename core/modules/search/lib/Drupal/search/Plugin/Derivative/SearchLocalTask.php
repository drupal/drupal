<?php

/**
 * @file
 * Contains \Drupal\search\Plugin\Derivative\SearchLocalTask.
 */

namespace Drupal\search\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeBase;

/**
 * Provides local tasks for each search plugin.
 */
class SearchLocalTask extends DerivativeBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    $this->derivatives = array();

    $default_info = search_get_default_plugin_info();
    if ($default_info) {
      foreach (\Drupal::service('plugin.manager.search')->getActiveDefinitions() as $plugin_id => $search_info) {
        $this->derivatives[$plugin_id] = array(
          'title' => $search_info['title'],
          'route_name' => 'search.view_' . $plugin_id,
          'tab_root_id' => 'search.plugins:' . $default_info['id'],
        );
        if ($plugin_id == $default_info['id']) {
          $this->derivatives[$plugin_id]['weight'] = -10;
        }
        else {
          $this->derivatives[$plugin_id]['weight'] = 0;
        }
      }
    }
    return $this->derivatives;
  }

}
