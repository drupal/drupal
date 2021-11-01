<?php

namespace Drupal\Core\Field;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a base for action plugins that update one or more fields.
 *
 * Example implementation:
 *
 * @code
 * class PromoteAndMakeSticky extends FieldUpdateActionBase {
 *
 *   protected function getFieldsToUpdate() {
 *     return [
 *       'status' => NODE_PROMOTED,
 *       'sticky' => NODE_STICKY,
 *     ];
 *   }
 *
 * }
 * @endcode
 *
 * @see \Drupal\Core\Action\Plugin\Action\PublishAction
 */
abstract class FieldUpdateActionBase extends ActionBase {

  /**
   * Gets an array of values to be set.
   *
   * @return array
   *   Array of values with field names as keys.
   */
  abstract protected function getFieldsToUpdate();

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    foreach ($this->getFieldsToUpdate() as $field => $value) {
      $entity->$field = $value;
    }
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\Core\Access\AccessResultInterface $result */
    $result = $object->access('update', $account, TRUE);

    foreach ($this->getFieldsToUpdate() as $field => $value) {
      $result->andIf($object->{$field}->access('edit', $account, TRUE));
    }

    return $return_as_object ? $result : $result->isAllowed();
  }

}
