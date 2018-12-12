<?php

namespace Drupal\block_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a context-aware block that uses a not-passed, non-required context.
 *
 * @Block(
 *   id = "test_context_aware_no_valid_context_options",
 *   admin_label = @Translation("Test context-aware block - no valid context options"),
 *   context_definitions = {
 *     "email" = @ContextDefinition("email", required = FALSE)
 *   }
 * )
 */
class TestContextAwareNoValidContextOptionsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => 'Rendered block with no valid context options',
    ];
  }

}
