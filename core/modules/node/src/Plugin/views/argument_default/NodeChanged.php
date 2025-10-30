<?php

namespace Drupal\node\Plugin\views\argument_default;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use Drupal\views\Attribute\ViewsArgumentDefault;

/**
 * Provides the 'changed' time of the current node as default argument value.
 */
#[ViewsArgumentDefault(
  id: 'node_changed',
  title: new TranslatableMarkup("Current node 'changed' time"),
)]
class NodeChanged extends NodeDateArgumentDefaultPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function getNodeDateValue(NodeInterface $node): int {
    return $node->getChangedTime();
  }

}
