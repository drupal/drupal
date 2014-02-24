<?php

/**
 * @file
 * Contains \Drupal\options\Type\ListIntegerItem.
 */

namespace Drupal\options\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'list_integer' field type.
 *
 * @FieldType(
 *   id = "list_integer",
 *   module = "options",
 *   label = @Translation("List (integer)"),
 *   description = @Translation("This field stores integer values from a list of allowed 'value => label' pairs, i.e. 'Lifetime in days': 1 => 1 day, 7 => 1 week, 31 => 1 month."),
 *   default_widget = "options_select",
 *   default_formatter = "list_default",
 *   settings = {
 *     "allowed_values" = { },
 *     "allowed_values_function" = ""
 *   }
 * )
 */
class ListIntegerItem extends ListItemBase {

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
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('Integer value'));

    return $properties;
  }

}
