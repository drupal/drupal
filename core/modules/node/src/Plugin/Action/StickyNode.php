<?php

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

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
    $entity->sticky = NODE_STICKY;
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andif($object->sticky->access('edit', $account, TRUE));
    return $return_as_object ? $access : $access->isAllowed();
  }

}
