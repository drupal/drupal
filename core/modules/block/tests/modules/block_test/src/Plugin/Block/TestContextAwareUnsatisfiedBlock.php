<?php

namespace Drupal\block_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a context-aware block.
 */
#[Block(
  id: "test_context_aware_unsatisfied",
  admin_label: new TranslatableMarkup("Test context-aware unsatisfied block"),
  context_definitions: [
    'user' => new EntityContextDefinition('entity:foobar'),
  ]
)]
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
