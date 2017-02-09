<?php

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

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
    $entity->setSticky(FALSE)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andIf($object->sticky->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
