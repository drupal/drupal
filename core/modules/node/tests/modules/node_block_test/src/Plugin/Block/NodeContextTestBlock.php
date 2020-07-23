<?php

namespace Drupal\node_block_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Node Context Test' block.
 *
 * @Block(
 *   id = "node_block_test_context",
 *   label = @Translation("Node Context Test"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
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
