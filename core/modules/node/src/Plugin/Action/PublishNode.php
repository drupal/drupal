<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Action\PublishNode.
 */

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

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

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
