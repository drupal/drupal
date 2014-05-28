<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Action\PublishNode.
 */

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\ActionBase;

/**
 * Publishes a node.
 *
 * @Action(
 *   id = "node_publish_action",
 *   label = @Translation("Publish selected content"),
 *   type = "node"
 * )
 */
class PublishNode extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->status = NODE_PUBLISHED;
    $entity->save();
  }

}
