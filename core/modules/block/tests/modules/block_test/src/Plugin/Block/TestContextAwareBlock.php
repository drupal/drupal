<?php

/**
 * @file
 * Contains \Drupal\block_test\Plugin\Block\TestContextAwareBlock.
 */

namespace Drupal\block_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a context-aware block.
 *
 * @Block(
 *   id = "test_context_aware",
 *   admin_label = @Translation("Test context-aware block"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user")
 *   }
 * )
 */
class TestContextAwareBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var $user \Drupal\user\UserInterface */
    $user = $this->getContextValue('user');
    return array(
      '#markup' => $user->getUsername(),
    );
  }

}
