<?php

declare(strict_types=1);

namespace Drupal\menu_test\Plugin\Menu\LocalTask;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\Request;

// cspell:ignore ragdoll

/**
 * Defines a local task plugin with a dynamic title.
 */
class TestTasksSettingsSub1 extends LocalTaskDefault {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitle(?Request $request = NULL) {
    return $this->t('Dynamic title for @class', ['@class' => 'TestTasksSettingsSub1']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['kittens:ragdoll'];
  }

}
