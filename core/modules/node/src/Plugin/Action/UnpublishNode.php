<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Action\UnpublishNode.
 */

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

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

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
