<?php

/**
 * @file
 * Contains \Drupal\node\Controller\NodeController.
 */

namespace Drupal\node\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;

/**
 * Returns responses for Node routes.
 */
class NodeController {

  /**
   * @todo Remove node_admin_nodes().
   */
  public function contentOverview() {
    module_load_include('admin.inc', 'node');
    return node_admin_nodes();
  }

  /**
   * @todo Remove node_add_page().
   */
  public function addPage() {
    module_load_include('pages.inc', 'node');
    return node_add_page();
  }

  /**
   * @todo Remove node_add().
   */
  public function add(EntityInterface $node_type) {
    module_load_include('pages.inc', 'node');
    return node_add($node_type);
  }

  /**
   * @todo Remove node_show().
   */
  public function revisionShow($node_revision) {
    $node_revision = entity_revision_load('node', $node_revision);
    return node_show($node_revision, TRUE);
  }

  /**
   * @todo Remove node_revision_overview().
   */
  public function revisionOverview(NodeInterface $node) {
    module_load_include('pages.inc', 'node');
    return node_revision_overview($node);
  }

}
