<?php

/**
 * @file
 * Contains \Drupal\language\Entity\Language.
 */

namespace Drupal\language\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\language\Exception\DeleteDefaultLanguageException;
use Drupal\language\LanguageInterface;

/**
 * Defines the Language entity.
 *
 * @ConfigEntityType(
 *   id = "language_entity",
 *   label = @Translation("Language"),
 *   controllers = {
 *     "list_builder" = "Drupal\language\LanguageListBuilder",
 *     "access" = "Drupal\language\LanguageAccessController",
 *     "form" = {
 *       "add" = "Drupal\language\Form\LanguageAddForm",
 *       "edit" = "Drupal\language\Form\LanguageEditForm",
 *       "delete" = "Drupal\language\Form\LanguageDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer languages",
 *   config_prefix = "entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "delete-form" = "language.delete",
 *     "edit-form" = "language.edit"
 *   }
 * )
 */
class Language extends ConfigEntityBase implements LanguageInterface {

  /**
   * The language ID (machine name).
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable label for the language.
   *
   * @var string
   */
  public $label;

  /**
   * The direction of language, either DIRECTION_LTR or DIRECTION_RTL.
   *
   * @var integer
   */
  public $direction = '';

  /**
   * The weight of the language, used in lists of languages.
   *
   * @var integer
   */
  public $weight = 0;

  /**
   * Locked languages cannot be edited.
   *
   * @var bool
   */
  public $locked = FALSE;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    // Languages are picked from a predefined list which is given in English.
    // For the uncommon case of custom languages the label should be given in
    // English.
    $this->langcode = 'en';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \RuntimeException
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    $default_language = \Drupal::service('language.default')->get();
    foreach ($entities as $entity) {
      if ($entity->id() == $default_language->id && !$entity->isUninstalling()) {
        throw new DeleteDefaultLanguageException('Can not delete the default language');
      }
    }
  }
}
