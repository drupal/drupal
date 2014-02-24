<?php

/**
 * @file
 * Contains \Drupal\options\Type\ListBooleanItem.
 */

namespace Drupal\options\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'list_boolean' field type.
 *
 * @FieldType(
 *   id = "list_boolean",
 *   module = "options",
 *   label = @Translation("Boolean"),
 *   description = @Translation("This field stores simple on/off or yes/no options."),
 *   default_widget = "options_buttons",
 *   default_formatter = "list_default",
 *   settings = {
 *     "allowed_values" = { },
 *     "allowed_values_function" = ""
 *   }
 * )
 */
class ListBooleanItem extends ListItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldDefinitionInterface $field_definition) {
    return parent::schema($field_definition) + array(
     'columns' => array(
       'value' => array(
         'type' => 'int',
         'not null' => FALSE,
       ),
     ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('boolean')
      ->setLabel(t('Boolean value'));

    return $properties;
  }

}
