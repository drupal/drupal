<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Tests\RequirementsPageTrait;

/**
 * Provides methods to conditionally enable db update functions and apply
 * pending db updates through the Update UI.
 *
 * This should be used only by classes extending \Drupal\Tests\BrowserTestBase.
 */
trait DbUpdatesTrait {

  use StringTranslationTrait;
  use RequirementsPageTrait;

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
    $this->updateRequirementsProblem();
    $this->clickLink($this->t('Continue'));
    $this->clickLink($this->t('Apply pending updates'));
    $this->checkForMetaRefresh();
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
