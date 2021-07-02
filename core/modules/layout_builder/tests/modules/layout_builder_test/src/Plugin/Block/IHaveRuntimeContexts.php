<?php

namespace Drupal\layout_builder_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Defines a class for a context-aware block.
 *
 * @Block(
 *   id = "i_have_runtime_contexts",
 *   admin_label = "Can I have runtime contexts",
 *   category = "Test",
 *   context_definitions = {
 *     "runtime_contexts" = @ContextDefinition("string", label = "Do you have runtime contexts")
 *   }
 * )
 */
class IHaveRuntimeContexts extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->getContextValue('runtime_contexts'),
    ];
  }

}
