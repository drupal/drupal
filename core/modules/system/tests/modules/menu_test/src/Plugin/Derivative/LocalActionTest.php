<?php

/**
 * @file
 * Contains \Drupal\menu_test\Plugin\Derivative\LocalActionTest.
 */

namespace Drupal\menu_test\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Test derivative to check local action title escaping.
 *
 * @see \Drupal\system\Tests\Menu\LocalActionTest
 */
class LocalActionTest extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives['example'] = $base_plugin_definition + [
      'title' => "<script>alert('Welcome to the derived jungle!')</script>",
    ];

    return $this->derivatives;
  }

}
