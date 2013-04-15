<?php

/**
 * @file
 * Contains \Drupal\edit\MetadataGeneratorInterface.
 */

namespace Drupal\edit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Interface for generating in-place editing metadata for an entity field.
 */
interface MetadataGeneratorInterface {

  /**
   * Generates in-place editing metadata for an entity field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being edited.
   * @param \Drupal\field\Plugin\Core\Entity\FieldInstance $instance
   *   The field instance of the field being edited.
   * @param string $langcode
   *   The name of the language for which the field is being edited.
   * @param string $view_mode
   *   The view mode the field should be rerendered in.
   * @return array
   *   An array containing metadata with the following keys:
   *   - label: the user-visible label for the field.
   *   - access: whether the current user may edit the field or not.
   *   - editor: which editor should be used for the field.
   *   - aria: the ARIA label.
   *   - custom: (optional) any additional metadata that the editor provides.
   */
  public function generate(EntityInterface $entity, FieldInstance $instance, $langcode, $view_mode);

}
