<?php

namespace Drupal\menu_test\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Test derivative with an unsafe string.
 */
class MenuLinkTestWithUnsafeTitle extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives['unsafe'] = [
        'title' => '<script>alert("Even more wild animals")</script>',
        'menu_name' => 'tools',
      ] + $base_plugin_definition;

    return $this->derivatives;
  }

}
