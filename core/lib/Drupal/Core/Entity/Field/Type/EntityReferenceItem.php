<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\Type\EntityReferenceItem.
 */

namespace Drupal\Core\Entity\Field\Type;

use Drupal\Core\Entity\Field\FieldItemBase;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Defines the 'entity_reference' entity field item.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - target_type: The entity type to reference.
 */
class EntityReferenceItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see EntityReferenceItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    // Definitions vary by entity type, so key them by entity type.
    $target_type = $this->definition['settings']['target_type'];

    if (!isset(self::$propertyDefinitions[$target_type])) {
      static::$propertyDefinitions[$target_type]['target_id'] = array(
        // @todo: Lookup the entity type's ID data type and use it here.
        'type' => 'integer',
        'label' => t('Entity ID'),
        'constraints' => array(
          'Range' => array('min' => 0),
        ),
      );
      static::$propertyDefinitions[$target_type]['entity'] = array(
        'type' => 'entity',
        'constraints' => array(
          'EntityType' => $target_type,
        ),
        'label' => t('Entity'),
        'description' => t('The referenced entity'),
        // The entity object is computed out of the entity ID.
        'computed' => TRUE,
        'read-only' => FALSE,
        'settings' => array('id source' => 'target_id'),
      );
    }
    return static::$propertyDefinitions[$target_type];
  }

  /**
   * Overrides \Drupal\Core\Entity\Field\FieldItemBase::__get().
   */
  public function __get($name) {
    $name = ($name == 'value') ? 'target_id' : $name;
    return parent::__get($name);
  }

  /**
   * Overrides \Drupal\Core\Entity\Field\FieldItemBase::get().
   */
  public function get($property_name) {
    $property_name = ($property_name == 'value') ? 'target_id' : $property_name;
    return parent::get($property_name);
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldItemInterface::__isset().
   */
  public function __isset($property_name) {
    $property_name = ($property_name == 'value') ? 'target_id' : $property_name;
    return parent::__isset($property_name);
  }

  /**
   * Overrides \Drupal\Core\Entity\Field\FieldItemBase::get().
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as value of the entity property, if no array is
    // given as this handles entity IDs and objects.
    if (isset($values) && !is_array($values)) {
      // Directly update the property instead of invoking the parent, so that
      // the entity property can take care of updating the ID property.
      $this->properties['entity']->setValue($values, $notify);
    }
    else {
      // Make sure that the 'entity' property gets set as 'target_id'.
      if (isset($values['target_id']) && !isset($values['entity'])) {
        $values['entity'] = $values['target_id'];
      }
      parent::setValue($values, $notify);
    }
  }
}
