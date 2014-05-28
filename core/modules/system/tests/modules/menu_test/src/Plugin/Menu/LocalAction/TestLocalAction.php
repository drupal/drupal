<?php

/**
 * @file
 * Contains \Drupal\menu_test\Plugin\Menu\LocalAction\TestLocalAction.
 */

namespace Drupal\menu_test\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;

/**
 * Defines a test local action plugin class.
 */
class TestLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return 'Title override';
  }

}
