<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Type\TaxonomyTermReferenceItem.
 */

namespace Drupal\taxonomy\Type;

use Drupal\Core\Entity\Field\FieldItemBase;
use Drupal\Core\TypedData\TypedDataInterface;

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
          'EntityType' => 'taxonomy_term',
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
   * Overrides \Drupal\Core\Entity\Field\FieldItemBase::get().
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the entity property, if no array is
    // given.
    if (isset($values) && !is_array($values)) {
      $values = array('entity' => $values);
    }
    // Make sure that the 'entity' property gets set as 'tid'.
    if (isset($values['tid']) && !isset($values['entity'])) {
      $values['entity'] = $values['tid'];
    }
    parent::setValue($values, $notify);
  }
}
