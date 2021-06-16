<?php

namespace Drupal\user;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;

/**
 * Defines the 'status' entity field type.
 *
 * @todo Consider making this a full field type plugin in
 *   https://www.drupal.org/project/drupal/issues/2936864.
 */
class StatusItem extends BooleanItem {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // Always generate a sample with an enabled status.
    $values['value'] = 1;
    return $values;
  }

}
