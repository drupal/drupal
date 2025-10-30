<?php

namespace Drupal\node\Plugin\views\argument_default;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use Drupal\views\Attribute\ViewsArgumentDefault;

/**
 * Provides the created time of the current node as default argument value.
 */
#[ViewsArgumentDefault(
  id: 'node_created',
  title: new TranslatableMarkup("Current node 'created' time"),
)]
class NodeCreated extends NodeDateArgumentDefaultPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function getNodeDateValue(NodeInterface $node): int {
    return $node->getCreatedTime();
  }

}
