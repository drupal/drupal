<?php

/**
 * @file
 * Contains \Drupal\node\Controller\NodeView.
 */

namespace Drupal\node\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;

/**
 * Returns responses for Node routes.
 */
class NodeView {

  /**
   * @todo Remove node_page_view().
   */
  public function page(NodeInterface $node) {
    return node_page_view($node);
  }

  /**
   * The _title_callback for the node.view route.
   *
   * @param \Drupal\node\NodeInterface $node
   */
  public function pageTitle(NodeInterface $node) {
    return String::checkPlain($node->label());
  }

}
