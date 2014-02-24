<?php

/**
 * @file
 * Contains \Drupal\options\Type\ListTextItem.
 */

namespace Drupal\options\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'list_text' field type.
 *
 * @FieldType(
 *   id = "list_text",
 *   module = "options",
 *   label = @Translation("List (text)"),
 *   description = @Translation("This field stores text values from a list of allowed 'value => label' pairs, i.e. 'US States': IL => Illinois, IA => Iowa, IN => Indiana."),
 *   default_widget = "options_select",
 *   default_formatter = "list_default",
 *   settings = {
 *     "allowed_values" = { },
 *     "allowed_values_function" = ""
 *   }
 * )
 */
class ListTextItem extends ListItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldDefinitionInterface $field_definition) {
    return parent::schema($field_definition) + array(
     'columns' => array(
       'value' => array(
         'type' => 'varchar',
         'length' => 255,
         'not null' => FALSE,
       ),
     ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldDefinitionInterface $field_definition) {
    $constraints = array('Length' => array('max' => 255));
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Text value'))
      ->setConstraints($constraints);

    return $properties;
  }

}
