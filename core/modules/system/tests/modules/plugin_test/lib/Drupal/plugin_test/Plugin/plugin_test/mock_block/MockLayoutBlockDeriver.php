<?php

/**
 * @file
 * Definition of Drupal\plugin_test\Plugin\plugin_test\mock_block\MockLayoutBlockDeriver.
 */

namespace Drupal\plugin_test\Plugin\plugin_test\mock_block;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Mock implementation of DerivativeInterface for the mock layout block plugin.
 *
 * @see \Drupal\plugin_test\Plugin\MockBlockManager
 */
class MockLayoutBlockDeriver implements DerivativeInterface {

  /**
   * Implements Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinition().
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    $derivatives = $this->getDerivativeDefinitions($base_plugin_definition);
    if (isset($derivatives[$derivative_id])) {
      return $derivatives[$derivative_id];
    }
  }

  /**
   * Implements Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinitions().
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // This isn't strictly necessary, but it helps reduce clutter in
    // DerivativePluginTest::testDerivativeDecorator()'s $expected variable.
    // Since derivative definitions don't need further deriving, we remove this
    // key from the returned definitions.
    unset($base_plugin_definition['derivative']);

    $derivatives = array(
      // Adding a NULL key signifies that the base plugin may also be used in
      // addition to the derivatives. In this case, we allow the administrator
      // to add a generic layout block to the page.
      NULL => $base_plugin_definition,

      // We also allow them to add a customized one. Here, we just mock the
      // customized one, but in a real implementation, this would be fetched
      // from some \Drupal::config() object.
      'foo' => array(
        'label' => t('Layout Foo'),
      ) + $base_plugin_definition,
    );

    return $derivatives;
  }
}
