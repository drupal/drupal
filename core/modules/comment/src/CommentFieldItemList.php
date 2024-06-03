<?php

namespace Drupal\comment;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an item list class for comment fields.
 */
class CommentFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function get($index) {
    // The Field API only applies the "field default value" to newly created
    // entities. In the specific case of the "comment status", though, we need
    // this default value to be also applied for existing entities created
    // before the comment field was added, which have no value stored for the
    // field.
    if ($index == 0 && empty($this->list)) {
      $field_default_value = $this->getFieldDefinition()->getDefaultValue($this->getEntity());
      return $this->appendItem($field_default_value[0]);
    }
    return parent::get($index);
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function offsetExists($offset) {
    // For consistency with what happens in get(), we force offsetExists() to
    // be TRUE for delta 0.
    if ($offset === 0) {
      return TRUE;
    }
    return parent::offsetExists($offset);
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($operation === 'edit') {
      // Only users with administer comments permission can edit the comment
      // status field.
      $result = AccessResult::allowedIfHasPermission($account ?: \Drupal::currentUser(), 'administer comments');
      return $return_as_object ? $result : $result->isAllowed();
    }
    if ($operation === 'view') {
      // Only users with "post comments" or "access comments" permission can
      // view the field value. The formatter,
      // Drupal\comment\Plugin\Field\FieldFormatter\CommentDefaultFormatter,
      // takes care of showing the thread and form based on individual
      // permissions, so if a user only has ‘post comments’ access, only the
      // form will be shown and not the comments.
      $result = AccessResult::allowedIfHasPermission($account ?: \Drupal::currentUser(), 'access comments')
        ->orIf(AccessResult::allowedIfHasPermission($account ?: \Drupal::currentUser(), 'post comments'));
      return $return_as_object ? $result : $result->isAllowed();
    }
    return parent::access($operation, $account, $return_as_object);
  }

}
