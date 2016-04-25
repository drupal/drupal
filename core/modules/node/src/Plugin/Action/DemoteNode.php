<?php

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

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

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->promote->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
