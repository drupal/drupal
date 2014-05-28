<?php

/**
 * @file
 * Contains \Drupal\menu_test\Plugin\Menu\LocalTask\TestTasksSettingsSub1.
 */

namespace Drupal\menu_test\Plugin\Menu\LocalTask;

use Drupal\Core\Menu\LocalTaskDefault;

class TestTasksSettingsSub1 extends LocalTaskDefault {

  /**
   * {@inheritdoc}
   */
  function getTitle() {
    return $this->t('Dynamic title for @class', array('@class' => 'TestTasksSettingsSub1'));
  }

}
