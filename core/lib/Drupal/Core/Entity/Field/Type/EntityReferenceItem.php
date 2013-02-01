<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\Type\EntityReferenceItem.
 */

namespace Drupal\Core\Entity\Field\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

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
   * Overrides \Drupal\Core\Entity\Field\FieldItemBase::setValue().
   */
  public function setValue($values) {
    // Treat the values as property value of the entity field, if no array
    // is given. That way we support setting the field by entity ID or object.
    if (!is_array($values)) {
      $values = array('entity' => $values);
    }

    // Entity is computed out of the ID, so we only need to update the ID. Only
    // set the entity field if no ID is given.
    if (isset($values['target_id'])) {
      $this->properties['target_id']->setValue($values['target_id']);
    }
    elseif (isset($values['entity'])) {
      $this->properties['entity']->setValue($values['entity']);
    }
    else {
      $this->properties['entity']->setValue(NULL);
    }
    unset($values['entity'], $values['target_id']);
    if ($values) {
      throw new \InvalidArgumentException('Property ' . key($values) . ' is unknown.');
    }
  }

  /**
   * Overrides \Drupal\Core\Entity\Field\FieldItemBase::get().
   */
  public function get($property_name) {
    $property_name = ($property_name == 'value') ? 'target_id' : $property_name;
    return parent::get($property_name);
  }
}
