<?php

/**
 * @file
 * Contains \Drupal\options\Type\ListFloatItem.
 */

namespace Drupal\options\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'list_float' field type.
 *
 * @FieldType(
 *   id = "list_float",
 *   module = "options",
 *   label = @Translation("List (float)"),
 *   description = @Translation("This field stores float values from a list of allowed 'value => label' pairs, i.e. 'Fraction': 0 => 0, .25 => 1/4, .75 => 3/4, 1 => 1."),
 *   default_widget = "options_select",
 *   default_formatter = "list_default",
 *   settings = {
 *     "allowed_values" = { },
 *     "allowed_values_function" = ""
 *   }
 * )
 */
class ListFloatItem extends ListItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldDefinitionInterface $field_definition) {
    return parent::schema($field_definition) + array(
     'columns' => array(
       'value' => array(
         'type' => 'float',
         'not null' => FALSE,
       ),
     ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('float')
      ->setLabel(t('Float value'));

    return $properties;
  }

}
