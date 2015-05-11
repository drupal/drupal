<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityFormBuilderInterface.
 */

namespace Drupal\Core\Entity;

/**
 * Builds entity forms.
 */
interface EntityFormBuilderInterface {

  /**
   * Gets the built and processed entity form for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be created or edited.
   * @param string $operation
   *   (optional) The operation identifying the form variation to be returned.
   *   Defaults to 'default'. This is typically used in routing:
   *   @code
   *   _entity_form: node.book_outline
   *   @endcode
   *   where "book_outline" is the value of $operation.
   * @param array $form_state_additions
   *   (optional) An associative array used to build the current state of the
   *   form. Use this to pass additional information to the form, such as the
   *   langcode. Defaults to an empty array.
   *
   * @code
   *   $form_state_additions['langcode'] = $langcode;
   *   $form = \Drupal::service('entity.form_builder')->getForm($entity, 'default', $form_state_additions);
   * @endcode
   *
   * @return array
   *   The processed form for the given entity and operation.
   */
  public function getForm(EntityInterface $entity, $operation = 'default', array $form_state_additions = array());

}
