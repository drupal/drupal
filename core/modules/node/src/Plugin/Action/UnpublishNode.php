<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Action\UnpublishNode.
 */

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\ActionBase;

/**
 * Unpublishes a node.
 *
 * @Action(
 *   id = "node_unpublish_action",
 *   label = @Translation("Unpublish selected content"),
 *   type = "node"
 * )
 */
class UnpublishNode extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->status = NODE_NOT_PUBLISHED;
    $entity->save();
  }

}
