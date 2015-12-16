<?php

/**
 * @file
 * Contains \Drupal\block_test\Plugin\Block\TestContextAwareUnsatisfiedBlock.
 */

namespace Drupal\block_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a context-aware block.
 *
 * @Block(
 *   id = "test_context_aware_unsatisfied",
 *   admin_label = @Translation("Test context-aware unsatisfied block"),
 *   context = {
 *     "user" = @ContextDefinition("entity:foobar")
 *   }
 * )
 */
class TestContextAwareUnsatisfiedBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => 'test',
    ];
  }

}
