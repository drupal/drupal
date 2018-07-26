<?php

namespace Drupal\Core\Field;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a item list class for changed fields.
 */
class ChangedFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultAccess($operation = 'view', AccountInterface $account = NULL) {
    // It is not possible to edit the changed field.
    return AccessResult::allowedIf($operation !== 'edit');
  }

  /**
   * {@inheritdoc}
   */
  public function hasAffectingChanges(FieldItemListInterface $original_items, $langcode) {
    // When saving entities in the user interface, the changed timestamp is
    // automatically incremented by ContentEntityForm::submitForm() even if
    // nothing was actually changed. Thus, the changed time needs to be
    // ignored when determining whether there are any actual changes in the
    // entity.
    return FALSE;
  }

}
