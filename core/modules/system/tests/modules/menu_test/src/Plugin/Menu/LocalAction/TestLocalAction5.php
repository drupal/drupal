<?php

namespace Drupal\menu_test\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;

/**
 * Defines a local action plugin with a dynamic title from user input.
 */
class TestLocalAction5 extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return "<script>alert('Welcome to the jungle!')</script>";
  }

}
