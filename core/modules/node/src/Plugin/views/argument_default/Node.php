<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\argument_default\Node.
 */

namespace Drupal\node\Plugin\views\argument_default;

use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Drupal\node\NodeInterface;

/**
 * Default argument plugin to extract a node.
 *
 * This plugin actually has no options so it odes not need to do a great deal.
 *
 * @ViewsArgumentDefault(
 *   id = "node",
 *   title = @Translation("Content ID from URL")
 * )
 */
class Node extends ArgumentDefaultPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    if (($node = $this->view->getRequest()->attributes->get('node')) && $node instanceof NodeInterface) {
      return $node->id();
    }
  }

}
