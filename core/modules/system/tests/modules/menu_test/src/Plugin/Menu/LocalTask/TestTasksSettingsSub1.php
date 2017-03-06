<?php

namespace Drupal\menu_test\Plugin\Menu\LocalTask;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class TestTasksSettingsSub1 extends LocalTaskDefault {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('Dynamic title for @class', ['@class' => 'TestTasksSettingsSub1']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['kittens:ragdoll'];
  }

}
