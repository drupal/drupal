<?php

namespace Drupal\update\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\update\UpdateManagerInterface;

/**
 * Hook implementations for update.
 */
class UpdateExtensionHooks {

  public function __construct(
    protected readonly UpdateManagerInterface $updateManager,
  ) {}

  /**
   * Implements hook_themes_installed().
   *
   * Implements hook_themes_uninstalled().
   *
   * Implements hook_modules_installed().
   *
   * Implements hook_modules_uninstalled().
   *
   * If extensions are installed or uninstalled, we invalidate the information
   * of available updates.
   */
  #[Hook('themes_installed')]
  #[Hook('themes_uninstalled')]
  #[Hook('modules_installed')]
  #[Hook('modules_uninstalled')]
  public function extensionsChanged($themes): void {
    // Clear all Update Status module data.
    $this->updateManager->reset();
  }

}
