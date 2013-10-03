<?php

/**
 * @file
 * Definition of Drupal\plugin_test\Plugin\plugin_test\mock_block\MockMenuBlockDeriver.
 */

namespace Drupal\plugin_test\Plugin\plugin_test\mock_block;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Mock implementation of DerivativeInterface for the mock menu block plugin.
 *
 * @see \Drupal\plugin_test\Plugin\MockBlockManager
 */
class MockMenuBlockDeriver implements DerivativeInterface {

  /**
   * Implements Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinition().
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    $derivatives = $this->getDerivativeDefinitions($base_plugin_definition);
    if (isset($derivatives[$derivative_id])) {
      return $derivatives[$derivative_id];
    }
  }

  /**
   * Implements Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinitions().
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    // This isn't strictly necessary, but it helps reduce clutter in
    // DerivativePluginTest::testDerivativeDecorator()'s $expected variable.
    // Since derivative definitions don't need further deriving, we remove this
    // key from the returned definitions.
    unset($base_plugin_definition['derivative']);

    // Here, we create some mock menu block definitions for menus that might
    // exist in a typical Drupal site. In a real implementation, we would query
    // Drupal's configuration to find out which menus actually exist.
    $derivatives = array(
      'main_menu' => array(
        'label' => t('Main menu'),
      ) + $base_plugin_definition,
      'navigation' => array(
        'label' => t('Navigation'),
      ) + $base_plugin_definition,
    );

    return $derivatives;
  }
}
