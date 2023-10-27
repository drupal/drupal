<?php

namespace Drupal\node_block_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'Node Context Test' block.
 */
#[Block(
  id: "node_block_test_context",
  admin_label: new TranslatableMarkup("Node Context Test"),
  context_definitions: [
    'node' => new EntityContextDefinition('entity:node', new TranslatableMarkup("Node")),
  ]
)]
class NodeContextTestBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getContextValue('node');
    return [
      '#type' => 'inline_template',
      '#template' => 'Displaying node #{{ id }}, revision #{{ revision_id }}: {{ title }}',
      '#context' => [
        'id' => $node->id(),
        'revision_id' => $node->getRevisionId(),
        'title' => $node->label(),
      ],
    ];
  }

}
