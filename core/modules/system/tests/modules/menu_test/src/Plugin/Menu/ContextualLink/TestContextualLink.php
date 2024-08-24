<?php

declare(strict_types=1);

namespace Drupal\menu_test\Plugin\Menu\ContextualLink;

use Drupal\Core\Menu\ContextualLinkDefault;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a contextual link plugin with a dynamic title from user input.
 */
class TestContextualLink extends ContextualLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle(?Request $request = NULL) {
    return "<script>alert('Welcome to the jungle!')</script>";
  }

}
