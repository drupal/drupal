<?php

/**
 * @file
 * Definition of Drupal\content_translation\ContentTranslationControllerInterface.
 */

namespace Drupal\content_translation;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for providing content translation.
 *
 * Defines a set of methods to allow any entity to be processed by the entity
 * translation UI.
 *
 * The content translation UI relies on the entity info to provide its features.
 * See the documentation of hook_entity_info() in the Entity API documentation
 * for more details on all the entity info keys that may be defined.
 *
 * To make Content Translation automatically support an entity type some keys
 * may need to be defined, but none of them is required unless the entity path
 * is different from ENTITY_TYPE/%ENTITY_TYPE (e.g. taxonomy/term/1), in which
 * case at least the 'canonical' key in the 'links' entity info property must be
 * defined.
 *
 * Every entity type needs a translation controller to be translated. This can
 * be specified through the "controllers['translation']" key in the entity
 * info. If an entity type is enabled for translation and no translation
 * controller is defined,
 * \Drupal\content_translation\ContentTranslationController will be assumed.
 * Every translation controller class must implement
 * \Drupal\content_translation\ContentTranslationControllerInterface.
 *
 * If the entity paths match the default patterns above and there is no need for
 * an entity-specific translation controller class, Content Translation will
 * provide built-in support for the entity. It will still be required to enable
 * translation for each translatable bundle.
 *
 * @see \Drupal\Core\Entity\EntityManagerInterface
 */
interface ContentTranslationControllerInterface {

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
