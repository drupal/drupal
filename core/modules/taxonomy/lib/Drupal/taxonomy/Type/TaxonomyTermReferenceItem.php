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
    // Treat the values as value of the entity property, if no array is
    // given as this handles entity IDs and objects.
    if (isset($values) && !is_array($values)) {
      // Directly update the property instead of invoking the parent, so that
      // the entity property can take care of updating the ID property.
      $this->properties['entity']->setValue($values, $notify);
    }
    else {
      // Make sure that the 'entity' property gets set as 'target_id'.
      if (isset($values['tid']) && !isset($values['entity'])) {
        $values['entity'] = $values['tid'];
      }
      parent::setValue($values, $notify);
    }
  }
}
