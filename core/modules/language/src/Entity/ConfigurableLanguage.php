<?php

/**
 * @file
 * Contains \Drupal\language\Entity\ConfigurableLanguage.
 */

namespace Drupal\language\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\language\ConfigurableLanguageManager;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\language\Exception\DeleteDefaultLanguageException;
use Drupal\language\ConfigurableLanguageInterface;

/**
 * Defines the ConfigurableLanguage entity.
 *
 * @ConfigEntityType(
 *   id = "configurable_language",
 *   label = @Translation("Language"),
 *   handlers = {
 *     "list_builder" = "Drupal\language\LanguageListBuilder",
 *     "access" = "Drupal\language\LanguageAccessControlHandler",
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
 *     "delete-form" = "entity.configurable_language.delete_form",
 *     "edit-form" = "entity.configurable_language.edit_form"
 *   }
 * )
 */
class ConfigurableLanguage extends ConfigEntityBase implements ConfigurableLanguageInterface {

  /**
   * The language ID (machine name).
   *
   * @var string
   */
  protected $id;

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
  protected $direction = self::DIRECTION_LTR;

  /**
   * The weight of the language, used in lists of languages.
   *
   * @var integer
   */
  protected $weight = 0;

  /**
   * Locked languages cannot be edited.
   *
   * @var bool
   */
  protected $locked = FALSE;

  /**
   * Used during saving to detect when the site becomes multilingual.
   *
   * This property is not saved to the language entity, but is needed for
   * detecting when to rebuild the services.
   *
   * @see \Drupal\language\Entity\ConfigurableLanguage::preSave()
   * @see \Drupal\language\Entity\ConfigurableLanguage::postSave()
   *
   * @var bool
   */
  protected $preSaveMultilingual;

  /**
   * {@inheritdoc}
   */
  public function isDefault() {
    return static::getDefaultLangcode() == $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return (bool) $this->locked;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    // Store whether or not the site is already multilingual so that we can
    // rebuild services if necessary during
    // \Drupal\language\Entity\ConfigurableLanguage::postSave().
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

    $language_manager = \Drupal::languageManager();
    $language_manager->reset();
    if ($language_manager instanceof ConfigurableLanguageManagerInterface) {
      $language_manager->updateLockedLanguageWeights();
    }

    // Update URL Prefixes for all languages after the
    // LanguageManagerInterface::getLanguages() cache is flushed.
    language_negotiation_url_prefixes_update();

    // If after adding this language the site will become multilingual, we need
    // to rebuild language services.
    if (!$this->preSaveMultilingual && !$update && $language_manager instanceof ConfigurableLanguageManagerInterface) {
      $language_manager::rebuildServices();
    }
    if (!$update) {
      // Install any available language configuration overrides for the language.
      \Drupal::service('language.config_factory_override')->installLanguageOverrides($this->id());
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \DeleteDefaultLanguageException
   *   Exception thrown if we're trying to delete the default language entity.
   *   This is not allowed as a site must have a default language.
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
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    $language_manager = \Drupal::languageManager();
    $language_manager->reset();
    if ($language_manager instanceof ConfigurableLanguageManagerInterface) {
      $language_manager->updateLockedLanguageWeights();
    }
    // If after deleting this language the site will become monolingual, we need
    // to rebuild language services.
    if (!\Drupal::languageManager()->isMultilingual()) {
      ConfigurableLanguageManager::rebuildServices();
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

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getDirection() {
    return $this->direction;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * Creates a configurable language object from a langcode.
   *
   * @param string $langcode
   *   The language code to use to create the object.
   *
   * @return $this
   *
   * @see \Drupal\Core\Language\LanguageManager::getStandardLanguageList()
   */
  public static function createFromLangcode($langcode) {
    $standard_languages = LanguageManager::getStandardLanguageList();
    if (!isset($standard_languages[$langcode])) {
      // Drupal does not know about this language, so we set its values with the
      // best guess. The user will be able to edit afterwards.
      return static::create(array(
        'id' => $langcode,
        'label' => $langcode,
      ));
    }
    else {
      // A known predefined language, details will be filled in properly.
      return static::create(array(
        'id' => $langcode,
        'label' => $standard_languages[$langcode][0],
        'direction' => isset($standard_languages[$langcode][2]) ? $standard_languages[$langcode][2] : static::DIRECTION_LTR,
      ));
    }
  }

}
