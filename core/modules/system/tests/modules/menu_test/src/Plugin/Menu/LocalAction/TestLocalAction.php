<?php

namespace Drupal\menu_test\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a test local action plugin class.
 */
class TestLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    return 'Title override';
  }

}
