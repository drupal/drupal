<?php

/**
 * @file
 * Contains \Drupal\menu_test\Plugin\Menu\LocalAction\TestLocalAction4.
 */

namespace Drupal\menu_test\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a local action plugin with a dynamic title.
 */
class TestLocalAction4 extends LocalActionDefault {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('My @arg action', array('@arg' => 'dynamic-title'));
  }

}
