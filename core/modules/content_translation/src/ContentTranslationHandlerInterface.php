<?php

/**
 * @file
 * Contains \Drupal\content_translation\ContentTranslationHandlerInterface.
 */

namespace Drupal\content_translation;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for providing content translation.
 *
 * Defines a set of methods to allow any entity to be processed by the entity
 * translation UI.
 */
interface ContentTranslationHandlerInterface {

  /**
   * Checks if the user can perform the given operation on translations of the
   * wrapped entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose translation has to be accessed.
   * @param $op
   *   The operation to be performed on the translation. Possible values are:
   *   - "create"
   *   - "update"
   *   - "delete"
   *
   * @return boolean
   *   TRUE if the operation may be performed, FALSE otherwise.
   */
  public function getTranslationAccess(EntityInterface $entity, $op);

  /**
   * Retrieves the source language for the translation being created.
   *
   * @param array $form_state
   *   The form state array.
   *
   * @return string
   *   The source language code.
   */
  public function getSourceLangcode(array $form_state);

  /**
   * Marks translations as outdated.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being translated.
   * @param string $langcode
   *   (optional) The language code of the updated language: all the other
   *   translations will be marked as outdated. Defaults to the entity language.
   */
  public function retranslate(EntityInterface $entity, $langcode = NULL);

  /**
   * Performs the needed alterations to the entity form.
   *
   * @param array $form
   *   The entity form to be altered to provide the translation workflow.
   * @param array $form_state
   *   The form state array.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being created or edited.
   */
  public function entityFormAlter(array &$form, array &$form_state, EntityInterface $entity);

}
