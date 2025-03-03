<?php

declare(strict_types=1);

namespace Drupal\layout_builder_form_block_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hooks for layout_builder_form_block_test.
 */
class LayoutBuilderFormBlockTestHooks {

  /**
   * Implements hook_block_alter().
   */
  #[Hook('block_alter')]
  public function blockAlter(&$definitions): void {
    // Allow a test block containing a form to be placed in navigation via
    // layout builder.
    $definitions['layout_builder_form_block_test_form_api_form_block']['allow_in_navigation'] = TRUE;
  }

}
