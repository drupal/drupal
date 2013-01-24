<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Type\TaxonomyTermReferenceItem.
 */

namespace Drupal\taxonomy\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'taxonomy_term_reference' entity field item.
 */
class TaxonomyTermReferenceItem extends FieldItemBase {

  /**
   * Property definitions of the contained properties.
   *
   * @see TaxonomyTermReferenceItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['tid'] = array(
        'type' => 'integer',
        'label' => t('Referenced taxonomy term id.'),
      );
      static::$propertyDefinitions['entity'] = array(
        'type' => 'entity',
        'constraints' => array(
          'entity type' => 'taxonomy_term',
        ),
        'label' => t('Term'),
        'description' => t('The referenced taxonomy term'),
        // The entity object is computed out of the tid.
        'computed' => TRUE,
        'read-only' => FALSE,
        'settings' => array('id source' => 'tid'),
      );
    }
    return static::$propertyDefinitions;
  }

  /**
   * Overrides \Drupal\Core\Entity\Field\FieldItemBase::setValue().
   */
  public function setValue($values) {
    // Treat the values as property value of the entity field, if no array
    // is given.
    if (!is_array($values)) {
      $values = array('entity' => $values);
    }

    // Entity is computed out of the ID, so we only need to update the ID. Only
    // set the entity field if no ID is given.
    if (isset($values['tid'])) {
      $this->properties['tid']->setValue($values['tid']);
    }
    elseif (isset($values['entity'])) {
      $this->properties['entity']->setValue($values['entity']);
    }
    else {
      $this->properties['entity']->setValue(NULL);
    }
    unset($values['entity'], $values['tid']);
    if ($values) {
      throw new \InvalidArgumentException('Property ' . key($values) . ' is unknown.');
    }
  }

}
