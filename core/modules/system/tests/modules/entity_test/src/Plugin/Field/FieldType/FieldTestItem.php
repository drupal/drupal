<?php

/**
 * @file
 * Contains \Drupal\entity_test\Plugin\Field\FieldType\FieldTestItem.
 */

namespace Drupal\entity_test\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;


/**
 * Defines the 'field_test' entity field type.
 *
 * @FieldType(
 *   id = "field_test",
 *   label = @Translation("Test field item"),
 *   description = @Translation("A field containing a plain string value."),
 *   category = @Translation("Field"),
 * )
 */
class FieldTestItem extends FieldItemBase {

  /**
   * Counts how many times all items of this type are saved.
   *
   * @var int
   */
  protected static $counter = [];

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // This is called very early by the user entity roles field. Prevent
    // early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Test value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 255,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    $name = $this->getFieldDefinition()->getName();
    static::$counter[$name] = 0;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $name = $this->getFieldDefinition()->getName();
    static::$counter[$name]++;

    // Overwrite the field value unless it is going to be overridden, in which
    // case its final value will already be different from the current one.
    if (!$this->getEntity()->isNew() && !$this->mustResave()) {
      $this->setValue('overwritten');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    // Determine whether the field value should be rewritten to the storage. We
    // always rewrite on create as we need to store a value including the entity
    // id.
    $resave = !$update || $this->mustResave();

    if ($resave) {
      $entity = $this->getEntity();
      $definition = $this->getFieldDefinition();
      $name = $definition->getName();
      $value = 'field_test:' . $name . ':' . $entity->id() . ':' . static::$counter[$name];
      $this->setValue($value);
    }

    return $resave;
  }

  /**
   * Checks whether the field item value should be resaved.
   *
   * @return bool
   *   TRUE if the item should be resaved, FALSE otherwise.
   */
  protected function mustResave() {
    return $this->getValue()['value'] == 'resave';
  }

}
