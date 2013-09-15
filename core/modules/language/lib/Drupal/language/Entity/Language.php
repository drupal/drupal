<?php

/**
 * @file
 * Contains \Drupal\language\Entity\Language.
 */

namespace Drupal\language\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\language\LanguageInterface;

/**
 * Defines the Language entity.
 *
 * @EntityType(
 *   id = "language_entity",
 *   label = @Translation("Language"),
 *   module = "language",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *     "list" = "Drupal\language\LanguageListController",
 *     "access" = "Drupal\language\LanguageAccessController",
 *     "form" = {
 *       "add" = "Drupal\language\Form\LanguageAddForm",
 *       "edit" = "Drupal\language\Form\LanguageEditForm",
 *       "delete" = "Drupal\language\Form\LanguageDeleteForm"
 *     }
 *   },
 *   config_prefix = "language.entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *     "uuid" = "uuid"
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
   * The language UUID.
   *
   * This is assigned automatically when the language is created.
   *
   * @var string
   */
  public $uuid;

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
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    parent::preSave($storage_controller);
    // Languages are picked from a predefined list which is given in English.
    // For the uncommon case of custom languages the label should be given in
    // English.
    $this->langcode = 'en';
  }

  /**
   * {@inheritdoc}
   */
  public function uri() {
    return array(
      'path' => 'admin/config/regional/language/edit/' . $this->id(),
      'options' => array(
        'entity_type' => $this->entityType,
        'entity' => $this,
      ),
    );
  }

}
