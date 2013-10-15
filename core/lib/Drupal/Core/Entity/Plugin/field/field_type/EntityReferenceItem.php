<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\field\field_type\EntityReferenceItem.
 */

namespace Drupal\Core\Entity\Plugin\field\field_type;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'entity_reference' entity field type.
 *
 * Supported settings (below the definition's 'settings' key) are:
 * - target_type: The entity type to reference. Required.
 * - target_bundle: (optional): If set, restricts the entity bundles which may
 *   may be referenced. May be set to an single bundle, or to an array of
 *   allowed bundles.
 *
 * @FieldType(
 *   id = "entity_reference",
 *   label = @Translation("Entity reference"),
 *   description = @Translation("An entity field containing an entity reference."),
 *   configurable = FALSE,
 *   constraints = {"ValidReference" = TRUE}
 * )
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
    $target_type = $this->definition['settings']['target_type'];

    // Definitions vary by entity type and bundle, so key them accordingly.
    $key = $target_type . ':';
    $key .= isset($this->definition['settings']['target_bundle']) ? $this->definition['settings']['target_bundle'] : '';

    if (!isset(static::$propertyDefinitions[$key])) {
      $target_type_info = \Drupal::entityManager()->getDefinition($target_type);
      if (is_subclass_of($target_type_info['class'], '\Drupal\Core\Entity\ContentEntityInterface')) {
        static::$propertyDefinitions[$key]['target_id'] = array(
          // @todo: Lookup the entity type's ID data type and use it here.
          // https://drupal.org/node/2107249
          'type' => 'integer',
          'label' => t('Entity ID'),
          'constraints' => array(
            'Range' => array('min' => 0),
          ),
        );
      }
      else {
        static::$propertyDefinitions[$key]['target_id'] = array(
          'type' => 'string',
          'label' => t('Entity ID'),
        );
      }

      static::$propertyDefinitions[$key]['entity'] = array(
        'type' => 'entity_reference',
        'constraints' => array(
          'EntityType' => $this->definition['settings']['target_type'],
        ),
        'label' => t('Entity'),
        'description' => t('The referenced entity'),
        // The entity object is computed out of the entity ID.
        'computed' => TRUE,
        'read-only' => FALSE,
      );
      if (isset($this->definition['settings']['target_bundle'])) {
        static::$propertyDefinitions[$key]['entity']['constraints']['Bundle'] = $this->definition['settings']['target_bundle'];
      }
    }
    return static::$propertyDefinitions[$key];
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
    if (isset($values) && !is_array($values)) {
      // Directly update the property instead of invoking the parent, so it can
      // handle objects and IDs.
      $this->properties['entity']->setValue($values, $notify);
      // If notify was FALSE, ensure the target_id property gets synched.
      if (!$notify) {
        $this->set('target_id', $this->properties['entity']->getTargetIdentifier(), FALSE);
      }
    }
    else {
      // Make sure that the 'entity' property gets set as 'target_id'.
      if (isset($values['target_id']) && !isset($values['entity'])) {
        $values['entity'] = $values['target_id'];
      }
      parent::setValue($values, $notify);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name) {
    // Make sure that the target ID and the target property stay in sync.
    if ($property_name == 'target_id') {
      $this->properties['entity']->setValue($this->target_id, FALSE);
    }
    elseif ($property_name == 'entity') {
      $this->set('target_id', $this->properties['entity']->getTargetIdentifier(), FALSE);
    }
    parent::onChange($property_name);
  }
}
