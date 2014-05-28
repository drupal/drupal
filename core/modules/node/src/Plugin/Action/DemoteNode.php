<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Action\DemoteNode.
 */

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\ActionBase;

/**
 * Demotes a node.
 *
 * @Action(
 *   id = "node_unpromote_action",
 *   label = @Translation("Demote selected content from front page"),
 *   type = "node"
 * )
 */
class DemoteNode extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->setPromoted(FALSE);
    $entity->save();
  }

}
