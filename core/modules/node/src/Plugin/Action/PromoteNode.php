<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Action\PromoteNode.
 */

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Promotes a node.
 *
 * @Action(
 *   id = "node_promote_action",
 *   label = @Translation("Promote selected content to front page"),
 *   type = "node"
 * )
 */
class PromoteNode extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->setPromoted(TRUE);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andif($object->promote->access('edit', $account, TRUE));
    return $return_as_object ? $access : $access->isAllowed();
  }

}
