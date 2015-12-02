<?php

/**
 * @file
 * Contains \Drupal\menu_test\Plugin\Derivative\LocalTaskTestWithUnsafeTitle.
 */

namespace Drupal\menu_test\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Test derivative to check local task title escaping.
 *
 * @see \Drupal\system\Tests\Menu\LocalTasksTest
 */
class LocalTaskTestWithUnsafeTitle extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives['unsafe'] = [
      'title' => "<script>alert('Welcome to the derived jungle!')</script>",
      'route_parameters' => ['bar' => 'unsafe'],
    ] + $base_plugin_definition;

    return $this->derivatives;
  }

}
