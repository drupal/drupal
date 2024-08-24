<?php

declare(strict_types=1);

namespace Drupal\layout_builder_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a class for a context-aware block.
 */
#[Block(
  id: "i_have_runtime_contexts",
  admin_label: new TranslatableMarkup("Can I have runtime contexts"),
  category: new TranslatableMarkup("Test"),
  context_definitions: [
    'runtime_contexts' => new ContextDefinition('string', 'Do you have runtime contexts'),
  ]
)]
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
