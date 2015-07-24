<?php

/**
 * @file
 * Contains \Drupal\menu_test\Plugin\Menu\LocalTask\TestTasksSettingsSub1.
 */

namespace Drupal\menu_test\Plugin\Menu\LocalTask;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class TestTasksSettingsSub1 extends LocalTaskDefault {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  function getTitle() {
    return $this->t('Dynamic title for @class', array('@class' => 'TestTasksSettingsSub1'));
  }

}
