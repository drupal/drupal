<?php

namespace Drupal\system\Tests\Update;

@trigger_error(__NAMESPACE__ . '\DbUpdatesTrait is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Use \Drupal\FunctionalTests\Update\DbUpdatesTrait instead. See https://www.drupal.org/node/2896640.', E_USER_DEPRECATED);

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Provides methods to conditionally enable db update functions and apply
 * pending db updates through the Update UI.
 *
 * This should be used only by classes extending \Drupal\simpletest\WebTestBase.
 *
 * @deprecated in drupal:8.4.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\FunctionalTests\Update\DbUpdatesTrait.
 * @see https://www.drupal.org/node/2896640
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
   * Applies any pending DB updates through the Update UI.
   */
  protected function applyUpdates() {
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
