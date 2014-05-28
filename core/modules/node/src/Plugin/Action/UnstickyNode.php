<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Action\UnstickyNode.
 */

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\ActionBase;

/**
 * Makes a node not sticky.
 *
 * @Action(
 *   id = "node_make_unsticky_action",
 *   label = @Translation("Make selected content not sticky"),
 *   type = "node"
 * )
 */
class UnstickyNode extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->sticky = NODE_NOT_STICKY;
    $entity->save();
  }

}
