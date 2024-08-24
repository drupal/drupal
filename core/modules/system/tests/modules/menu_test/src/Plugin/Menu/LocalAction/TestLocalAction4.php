<?php

declare(strict_types=1);

namespace Drupal\menu_test\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a local action plugin with a dynamic title.
 */
class TestLocalAction4 extends LocalActionDefault {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitle(?Request $request = NULL) {
    return $this->t('My @arg action', ['@arg' => 'dynamic-title']);
  }

}
