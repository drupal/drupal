<?php

declare(strict_types=1);

namespace Drupal\menu_test\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a local action plugin with a dynamic title from user input.
 */
class TestLocalAction5 extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle(?Request $request = NULL) {
    return "<script>alert('Welcome to the jungle!')</script>";
  }

}
