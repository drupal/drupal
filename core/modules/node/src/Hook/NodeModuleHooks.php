<?php

declare(strict_types=1);

namespace Drupal\node\Hook;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Module hook implementations for node.
 */
class NodeModuleHooks {

  public function __construct(
    private readonly ModuleHandlerInterface $moduleHandler,
  ) {

  }

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled(array $modules): void {
    // Check if any of the newly enabled modules require the node_access table
    // to be rebuilt.
    if (!node_access_needs_rebuild() && $this->moduleHandler->hasImplementations('node_grants', $modules)) {
      node_access_needs_rebuild(TRUE);
    }
  }

  /**
   * Implements hook_modules_uninstalled().
   */
  #[Hook('modules_uninstalled')]
  public function modulesUninstalled($modules): void {
    // Check whether any of the disabled modules implemented hook_node_grants(),
    // in which case the node access table needs to be rebuilt.
    foreach ($modules as $module) {
      // At this point, the module is already disabled, but its code is still
      // loaded in memory. Module functions must no longer be called. We only
      // check whether a hook implementation function exists and do not invoke
      // it. Node access also needs to be rebuilt if language module is disabled
      // to remove any language-specific grants.
      if (!node_access_needs_rebuild() && ($this->moduleHandler->hasImplementations('node_grants', $module) || $module == 'language')) {
        node_access_needs_rebuild(TRUE);
      }
    }
    // If there remains no more node_access module, rebuilding will be
    // straightforward, we can do it right now.
    if (node_access_needs_rebuild() && !$this->moduleHandler->hasImplementations('node_grants')) {
      node_access_rebuild();
    }
  }

}
