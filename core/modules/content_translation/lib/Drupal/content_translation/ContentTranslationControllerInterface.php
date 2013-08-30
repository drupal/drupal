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
 * case at least the 'menu_base_path' key must be defined. This is used to
 * determine the view and edit paths if they follow the standard path patterns.
 * Otherwise the 'menu_view_path' and 'menu_edit_path' keys must be defined. If
 * an entity type is enabled for translation and no menu path key is defined,
 * the following defaults will be assumed:
 * - menu_base_path: ENTITY_TYPE/%ENTITY_TYPE
 * - menu_view_path: ENTITY_TYPE/%ENTITY_TYPE
 * - menu_edit_path: ENTITY_TYPE/%ENTITY_TYPE/edit
 * The menu base path is also used to reliably alter menu router information to
 * provide the translation overview page for any entity.
 * If the entity uses a menu loader different from %ENTITY_TYPE also the 'menu
 * path wildcard' info key needs to be defined.
 *
 * Every entity type needs a translation controller to be translated. This can
 * be specified through the "controllers['translation']" key in the entity
 * info. If an entity type is enabled for translation and no translation
 * controller is defined, Drupal\content_translation\ContentTranslationController
 * will be assumed. Every translation controller class must implement
 * Drupal\content_translation\ContentTranslationControllerInterface.
 *
 * If the entity paths match the default patterns above and there is no need for
 * an entity-specific translation controller class, Content Translation will
 * provide built-in support for the entity. It will still be required to enable
 * translation for each translatable bundle.
 *
 * Additionally some more entity info keys can be defined to further customize
 * the translation UI. The content translation info is an associative array that
 * has to match the following structure. Two nested arrays keyed respectively
 * by the 'translation' key and the 'content_translation' key. Elements:
 * - access callback: The access callback for the translation pages. Defaults to
 *   'entity_translation_translate_access'.
 * - access arguments: The access arguments for the translation pages. By
 *   default only the entity object is passed to the access callback.
 *
 * This is how entity info would look for a module defining a new translatable
 * entity type:
 * @code
 *   function mymodule_entity_info_alter(array &$info) {
 *     $info['myentity'] += array(
 *       'menu_base_path' => 'mymodule/myentity/%my_entity_loader',
 *       'menu_path_wildcard' => '%my_entity_loader',
 *       'translation' => array(
 *         'content_translation' => array(
 *           'access_callback' => 'mymodule_myentity_translate_access',
 *           'access_arguments' => array(2),
 *         ),
 *       ),
 *     );
 *     $info['myentity']['controllers'] += array('translation' => 'Drupal\mymodule\MyContentTranslationController');
 *   }
 * @endcode
 *
 * @see \Drupal\Core\Entity\EntityManager
 */
interface ContentTranslationControllerInterface {

  /**
   * Returns the base path for the current entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to the path should refer to.
   *
   * @return string
   *   The entity base path.
   */
  public function getBasePath(EntityInterface $entity);

  /**
   * Returns the path of the entity edit form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to the path should refer to.
   *
   * @return string
   *   The entity edit path.
   */
  public function getEditPath(EntityInterface $entity);

  /**
   * Returns the path of the entity view page.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to the path should refer to.
   *
   * @return string
   *   The entity view path.
   */
  public function getViewPath(EntityInterface $entity);

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
   * Removes the translation values from the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose values should be removed.
   * @param string $langcode
   *   The language code identifying the translation being deleted.
   */
  public function removeTranslation(EntityInterface $entity, $langcode);

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
