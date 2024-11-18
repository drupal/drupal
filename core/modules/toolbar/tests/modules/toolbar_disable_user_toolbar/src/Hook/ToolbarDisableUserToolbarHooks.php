<?php

declare(strict_types=1);

namespace Drupal\toolbar_disable_user_toolbar\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for toolbar_disable_user_toolbar.
 */
class ToolbarDisableUserToolbarHooks {

  /**
   * Implements hook_toolbar_alter().
   */
  #[Hook('toolbar_alter')]
  public function toolbarAlter(&$items): void {
    unset($items['user']);
  }

}
