<?php

namespace Drupal\language\Entity;

use Drupal\Core\Config\Action\Attribute\ActionMethod;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\language\ConfigurableLanguageManager;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\language\Exception\DeleteDefaultLanguageException;
use Drupal\language\ConfigurableLanguageInterface;
use Drupal\language\Form\LanguageAddForm;
use Drupal\language\Form\LanguageDeleteForm;
use Drupal\language\Form\LanguageEditForm;
use Drupal\language\LanguageAccessControlHandler;
use Drupal\language\LanguageListBuilder;

/**
 * Defines the ConfigurableLanguage entity.
 */
#[ConfigEntityType(
  id: 'configurable_language',
  label: new TranslatableMarkup('Language'),
  label_collection: new TranslatableMarkup('Languages'),
  label_singular: new TranslatableMarkup('language'),
  label_plural: new TranslatableMarkup('languages'),
  config_prefix: 'entity',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'weight' => 'weight',
  ], handlers: [
    'list_builder' => LanguageListBuilder::class,
    'access' => LanguageAccessControlHandler::class,
    'form' => [
      'add' => LanguageAddForm::class,
      'edit' => LanguageEditForm::class,
      'delete' => LanguageDeleteForm::class,
    ],
  ],
  links: [
    'delete-form' => '/admin/config/regional/language/delete/{configurable_language}',
    'edit-form' => '/admin/config/regional/language/edit/{configurable_language}',
    'collection' => '/admin/config/regional/language',
  ],
  admin_permission: 'administer languages',
  label_count: [
    'singular' => '@count language',
    'plural' => '@count languages',
  ],
  config_export: [
    'id',
    'label',
    'direction',
    'weight',
    'locked',
  ],
)]
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
  protected $label;

  /**
   * The direction of language, either DIRECTION_LTR or DIRECTION_RTL.
   *
   * @var int
   */
  protected $direction = self::DIRECTION_LTR;

  /**
   * The weight of the language, used in lists of languages.
   *
   * @var int
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
   * @var bool
   *
   * @see \Drupal\language\Entity\ConfigurableLanguage::preSave()
   * @see \Drupal\language\Entity\ConfigurableLanguage::postSave()
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
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $language_manager = \Drupal::languageManager();
    $language_manager->reset();
    if (!$this->isLocked() && $language_manager instanceof ConfigurableLanguageManagerInterface && !$this->isSyncing()) {
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

    if (!$this->isLocked() && !$update) {
      // Add language to the list of language domains.
      $config = \Drupal::configFactory()->getEditable('language.negotiation');
      $domains = $config->get('url.domains');
      $domains[$this->id()] = '';
      $config->set('url.domains', $domains)->save(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\language\Exception\DeleteDefaultLanguageException
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
    $entity = reset($entities);
    if ($language_manager instanceof ConfigurableLanguageManagerInterface && !$entity->isUninstalling() && !$entity->isSyncing()) {
      $language_manager->updateLockedLanguageWeights();
    }
    // If after deleting this language the site will become monolingual, we need
    // to rebuild language services.
    if (!\Drupal::languageManager()->isMultilingual()) {
      ConfigurableLanguageManager::rebuildServices();
    }

    // Remove language from language prefix and domain list.
    $config = \Drupal::configFactory()->getEditable('language.negotiation');
    $config->clear('url.prefixes.' . $entity->id());
    $config->clear('url.domains.' . $entity->id());
    $config->save(TRUE);
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
  #[ActionMethod(adminLabel: new TranslatableMarkup('Set Language name'), pluralize: FALSE)]
  public function setName($name) {
    $this->label = $name;

    return $this;
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
   * {@inheritdoc}
   */
  #[ActionMethod(adminLabel: new TranslatableMarkup('Set weight'), pluralize: FALSE)]
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
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
      return static::create([
        'id' => $langcode,
        'label' => $langcode,
      ]);
    }
    else {
      // A known predefined language, details will be filled in properly.
      return static::create([
        'id' => $langcode,
        'label' => $standard_languages[$langcode][0],
        'direction' => $standard_languages[$langcode][2] ?? static::DIRECTION_LTR,
      ]);
    }
  }

}
