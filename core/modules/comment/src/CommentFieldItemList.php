<?php

/**
 * @file
 * Contains \Drupal\comment\CommentFieldItemList.
 */

namespace Drupal\comment;

use Drupal\Core\Field\FieldItemList;

/**
 * Defines a item list class for comment fields.
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
  public function offsetExists($offset) {
    // For consistency with what happens in get(), we force offsetExists() to
    // be TRUE for delta 0.
    if ($offset === 0) {
      return TRUE;
    }
    return parent::offsetExists($offset);
  }

}
