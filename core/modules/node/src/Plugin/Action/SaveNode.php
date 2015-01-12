<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Action\SaveNode.
 */

namespace Drupal\node\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an action that can save any entity.
 *
 * @Action(
 *   id = "node_save_action",
 *   label = @Translation("Save content"),
 *   type = "node"
 * )
 */
class SaveNode extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    return $object->access('update', $account, $return_as_object);
  }

}
