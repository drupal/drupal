<?php

namespace Drupal\menu_test\Plugin\Menu\LocalTask;

use Drupal\Core\Menu\LocalTaskDefault;
use Symfony\Component\HttpFoundation\Request;

class TestTaskWithUserInput extends LocalTaskDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle(?Request $request = NULL) {
    return "<script>alert('Welcome to the jungle!')</script>";
  }

}
