<?php

/**
 * @file
 * Contains \Drupal\plugin_test\Controller\PluginTest.
 */

namespace Drupal\plugin_test\Controller;

use Drupal\plugin_test\Plugin\CachedMockBlockManager;

/**
 * Returns a test page containing plugin labels.
 */
class PluginTest {

  /**
   * Prints plugin labels for testing.
   *
   * @return array
   *   A renderable array of plugin labels.
   */
  public function testDefinitions() {
    $manager = new CachedMockBlockManager();
    $output = array();
    foreach ($manager->getDefinitions() as $plugin_id => $definition) {
      $output[$plugin_id] = array(
        '#markup' => $definition['label'],
      );
    }
    return $output;
  }

}
