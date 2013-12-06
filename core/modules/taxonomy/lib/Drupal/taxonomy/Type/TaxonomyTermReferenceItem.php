<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Type\TaxonomyTermReferenceItem.
 */

namespace Drupal\taxonomy\Type;

use Drupal\Core\Field\ConfigEntityReferenceItemBase;

/**
 * Defines the 'taxonomy_term_reference' entity field item.
 */
class TaxonomyTermReferenceItem extends ConfigEntityReferenceItemBase {

  /**
   * Property definitions of the contained properties.
   *
   * @see TaxonomyTermReferenceItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    $this->definition->setSetting('target_type', 'taxonomy_term');
    return parent::getPropertyDefinitions();
  }

}
