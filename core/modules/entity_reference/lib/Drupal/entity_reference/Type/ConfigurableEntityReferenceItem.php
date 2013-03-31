<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Type\ConfigurableEntityReferenceItem.
 */

namespace Drupal\entity_reference\Type;

use Drupal\Core\Entity\Field\Type\EntityReferenceItem;

/**
 * Defines the 'entity_reference_configurable' entity field item.
 *
 * Extends the Core 'entity_reference' entity field item with properties for
 * revision ids, labels (for autocreate) and access.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - target_type: The entity type to reference.
 */
class ConfigurableEntityReferenceItem extends EntityReferenceItem {

  /**
   * Definitions of the contained properties.
   *
   * @see ConfigurableEntityReferenceItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Overrides \Drupal\Core\Entity\Field\Type\EntityReferenceItem::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    // Definitions vary by entity type, so key them by entity type.
    $target_type = $this->definition['settings']['target_type'];

    if (!isset(self::$propertyDefinitions[$target_type])) {
      // Call the parent to define the target_id and entity properties.
      parent::getPropertyDefinitions();

      static::$propertyDefinitions[$target_type]['revision_id'] = array(
        // @todo: Lookup the entity type's ID data type and use it here.
        'type' => 'integer',
        'label' => t('Revision ID'),
        'constraints' => array(
          'Range' => array('min' => 0),
        ),
      );
      static::$propertyDefinitions[$target_type]['label'] = array(
        'type' => 'string',
        'label' => t('Label (auto-create)'),
        'computed' => TRUE,
      );
      static::$propertyDefinitions[$target_type]['access'] = array(
        'type' => 'boolean',
        'label' => t('Access'),
        'computed' => TRUE,
      );
    }
    return static::$propertyDefinitions[$target_type];
  }

  /**
   * Overrides \Drupal\Core\Entity\Field\Type\EntityReferenceItem::setValue().
   */
  public function setValue($values) {
    // Treat the values as property value of the entity field, if no array
    // is given. That way we support setting the field by entity ID or object.
    if (!is_array($values)) {
      $values = array('entity' => $values);
    }

    foreach (array('revision_id', 'access', 'label') as $property) {
      if (array_key_exists($property, $values)) {
        $this->properties[$property]->setValue($values[$property]);
        unset($values[$property]);
      }
    }

    // Pass the remaining values through to the parent class.
    parent::setValue($values);
  }

}
