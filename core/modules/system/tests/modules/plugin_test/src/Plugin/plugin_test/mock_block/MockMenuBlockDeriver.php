<?php

namespace Drupal\plugin_test\Plugin\plugin_test\mock_block;

use Drupal\Component\Plugin\Derivative\DeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Mock implementation of DeriverInterface for the mock menu block plugin.
 *
 * @see \Drupal\plugin_test\Plugin\MockBlockManager
 */
class MockMenuBlockDeriver implements DeriverInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    $derivatives = $this->getDerivativeDefinitions($base_plugin_definition);
    if (isset($derivatives[$derivative_id])) {
      return $derivatives[$derivative_id];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // This isn't strictly necessary, but it helps reduce clutter in
    // DerivativePluginTest::testDerivativeDecorator()'s $expected variable.
    // Since derivative definitions don't need further deriving, we remove this
    // key from the returned definitions.
    unset($base_plugin_definition['deriver']);

    // Here, we create some mock menu block definitions for menus that might
    // exist in a typical Drupal site. In a real implementation, we would query
    // Drupal's configuration to find out which menus actually exist.
    $derivatives = [
      'main_menu' => [
        'label' => $this->t('Main menu'),
      ] + $base_plugin_definition,
      'navigation' => [
        'label' => $this->t('Navigation'),
      ] + $base_plugin_definition,
      'foo' => [
        // Instead of the derivative label, the specific label will be used.
        'label' => $this->t('Derivative label'),
        // This setting will be merged in.
        'setting' => 'default',
      ] + $base_plugin_definition,
    ];

    return $derivatives;
  }

}
