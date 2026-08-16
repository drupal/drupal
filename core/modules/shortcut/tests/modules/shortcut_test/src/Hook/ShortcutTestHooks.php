<?php

declare(strict_types=1);

namespace Drupal\shortcut_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;

/**
 * Hooks implementations for shortcut_test module.
 */
class ShortcutTestHooks {

  /**
   * ShortcutTestHooks constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(
    protected StateInterface $state,
  ) {
  }

  /**
   * Implements hook_block_alter().
   *
   * @see \Drupal\Tests\shortcut\Functional\testNavigationSafeBlockDefinition()
   */
  #[Hook('block_alter')]
  public function blockAlter(&$definitions): void {
    if ($this->state->get('navigation_safe_alter')) {
      $definitions['navigation_shortcuts']['allow_in_navigation'] = FALSE;
    }
  }

}
