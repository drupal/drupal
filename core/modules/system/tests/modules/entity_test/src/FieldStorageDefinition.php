<?php

/**
 * @file
 * Contains \Drupal\entity_test\FieldStorageDefinition.
 */

namespace Drupal\entity_test;

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * A custom field storage definition class.
 *
 * For convenience we extend from BaseFieldDefinition although this should not
 * implement FieldDefinitionInterface.
 *
 * @todo Provide and make use of a proper FieldStorageDefinition class instead:
 *   https://drupal.org/node/2280639.
 */
class FieldStorageDefinition extends BaseFieldDefinition {

  /**
   * {@inheritdoc}
   */
  public function isBaseField() {
    return FALSE;
  }

}
