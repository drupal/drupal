<?php

/**
 * @file
 * Contains \Drupal\language\Entity\Language.
 */

namespace Drupal\language\Entity;

use Drupal\Core\Language\Language as LanguageObject;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
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
 *     "delete-form" = "entity.language_entity.delete_form",
 *     "edit-form" = "entity.language_entity.edit_form"
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
   * Flag to indicate if the language entity is the default site language.
   *
   * This property is not saved to the language entity since there can be only
   * one default language. It is saved to system.site:langcode and set on the
   * container using the language.default service in when the entity is saved.
   * The value is set correctly when a language entity is created or loaded.
   *
   * @see \Drupal\language\Entity\Language::postSave()
   * @see \Drupal\language\Entity\Language::isDefault()
   * @see \Drupal\language\Entity\Language::setDefault()
   *
   * @var bool
   */
  protected $default;

  /**
   * Used during saving to detect when the site becomes multilingual.
   *
   * This property is not saved to the language entity, but is needed for
   * detecting when to rebuild the services.
   *
   * @see \Drupal\language\Entity\Language::preSave()
   * @see \Drupal\language\Entity\Language::postSave()
   *
   * @var bool
   */
  protected $preSaveMultilingual;

  /**
   * Sets the default flag on the language entity.
   *
   * @param bool $default
   */
  public function setDefault($default) {
    $this->default = $default;
  }

  /**
   * Checks if the language entity is the site default language.
   *
   * @return bool
   *   TRUE if the language entity is the site default language, FALSE if not.
   */
  public function isDefault() {
    if (!isset($this->default)) {
      return static::getDefaultLangcode() == $this->id();
    }
    return $this->default;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    // Store whether or not the site is already multilingual so that we can
    // rebuild services if necessary during
    // \Drupal\language\Entity\Language::postSave().
    $this->preSaveMultilingual = \Drupal::languageManager()->isMultilingual();
    // Languages are picked from a predefined list which is given in English.
    // For the uncommon case of custom languages the label should be given in
    // English.
    $this->langcode = 'en';
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Only set the default language and save it to system.site configuration if
    // it needs to updated.
    if ($this->isDefault() && static::getDefaultLangcode() != $this->id()) {
      // Update the config. Saving the configuration fires and event that causes
      // the container to be rebuilt.
      \Drupal::config('system.site')->set('langcode', $this->id())->save();
      \Drupal::service('language.default')->set($this->toLanguageObject());
    }

    $language_manager = \Drupal::languageManager();
    $language_manager->reset();
    if ($language_manager instanceof ConfigurableLanguageManagerInterface) {
      $language_manager->updateLockedLanguageWeights();
    }

    // Update URL Prefixes for all languages after the new default language is
    // propagated and the LanguageManagerInterface::getLanguages() cache is
    // flushed.
    language_negotiation_url_prefixes_update();

    // If after adding this language the site will become multilingual, we need
    // to rebuild language services.
    if (!$this->preSaveMultilingual && !$update && $language_manager instanceof ConfigurableLanguageManagerInterface) {
      $language_manager::rebuildServices();
    }
  }

  /**
   * Converts the Language entity to a Language value object.
   *
   * @todo fix return type hint after https://drupal.org/node/2246665 and
   *   https://drupal.org/node/2246679.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   The language configuration entity expressed as a Language value object.
   */
  protected function toLanguageObject() {
    return new LanguageObject(array(
      'id' => $this->id(),
      'name' => $this->label(),
      'direction' => $this->direction,
      'weight' => $this->weight,
      'locked' => $this->locked,
      'default' => $this->default,
    ));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \RuntimeException
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    $default_langcode = static::getDefaultLangcode();
    foreach ($entities as $entity) {
      if ($entity->id() == $default_langcode && !$entity->isUninstalling()) {
        throw new DeleteDefaultLanguageException('Can not delete the default language');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($property_name) {
    if ($property_name == 'default') {
      return $this->isDefault();
    }
    else {
      return parent::get($property_name);
    }
  }

  /**
   * Gets the default langcode.
   *
   * @return string
   *   The current default langcode.
   */
  protected static function getDefaultLangcode() {
    $language = \Drupal::service('language.default')->get();
    return $language->getId();
  }

}
