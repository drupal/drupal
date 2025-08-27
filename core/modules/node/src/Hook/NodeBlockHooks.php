<?php

declare(strict_types=1);

namespace Drupal\node\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Block hook implementations for node.
 */
class NodeBlockHooks {

  /**
   * Implements hook_block_alter().
   */
  #[Hook('block_alter')]
  public function blockAlter(&$definitions): void {
    // Hide the deprecated Syndicate block from the UI.
    $definitions['node_syndicate_block']['_block_ui_hidden'] = TRUE;
  }

}
