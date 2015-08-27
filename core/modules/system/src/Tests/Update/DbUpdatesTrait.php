<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Update\DbUpdatesTrait.
 */

namespace Drupal\system\Tests\Update;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Provides methods to conditionally enable db update functions and run updates.
 */
trait DbUpdatesTrait {

  use StringTranslationTrait;

  /**
   * Enables db updates until the specified index.
   *
   * @param string $module
   *   The name of the module defining the update functions.
   * @param string $group
   *   A name identifying the group of update functions to enable.
   * @param $index
   *   The index of the last update function to run.
   */
  protected function enableUpdates($module, $group, $index) {
    $this->container->get('state')->set($module . '.db_updates.' . $group, $index);
  }

  /**
   * Runs DB updates.
   */
  protected function runUpdates() {
    $this->drupalGet(Url::fromRoute('system.db_update'));
    $this->clickLink($this->t('Continue'));
    $this->clickLink($this->t('Apply pending updates'));
  }

  /**
   * Conditionally load Update API functions for the specified group.
   *
   * @param string $module
   *   The name of the module defining the update functions.
   * @param string $group
   *   A name identifying the group of update functions to enable.
   */
  public static function includeUpdates($module, $group) {
    if ($index = \Drupal::state()->get($module . '.db_updates.' . $group)) {
      module_load_include('inc', $module, 'update/' . $group . '_' . $index);
    }
  }

}
