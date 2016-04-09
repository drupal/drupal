<?php

namespace Drupal\menu_test\Plugin\Menu\ContextualLink;

use Drupal\Core\Menu\ContextualLinkDefault;

/**
 * Defines a contextual link plugin with a dynamic title from user input.
 */
class TestContextualLink extends ContextualLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return "<script>alert('Welcome to the jungle!')</script>";
  }

}
