<?php

namespace Drupal\block_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a context-aware block that uses a not-passed, non-required context.
 */
#[Block(
  id: "test_context_aware_no_valid_context_options",
  admin_label: new TranslatableMarkup("Test context-aware block - no valid context options"),
  context_definitions: [
    'user' => new ContextDefinition(data_type: 'email', required: FALSE),
  ]
)]
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
