<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Action\StickyNode.
 */

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\ActionBase;

/**
 * Makes a node sticky.
 *
 * @Action(
 *   id = "node_make_sticky_action",
 *   label = @Translation("Make selected content sticky"),
 *   type = "node"
 * )
 */
class StickyNode extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->status = NODE_PUBLISHED;
    $entity->sticky = NODE_STICKY;
    $entity->save();
  }

}
