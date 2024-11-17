<?php

namespace Drupal\language\Entity;

use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\ContentLanguageSettingsException;
use Drupal\language\ContentLanguageSettingsInterface;

/**
 * Defines the ContentLanguageSettings entity.
 */
#[ConfigEntityType(
  id: 'language_content_settings',
  label: new TranslatableMarkup('Content language settings'),
  label_collection: new TranslatableMarkup('Content language settings'),
  label_singular: new TranslatableMarkup('content language setting'),
  label_plural: new TranslatableMarkup('content languages settings'),
  config_prefix: 'content_settings',
  entity_keys: [
    'id' => 'id',
  ],
  admin_permission: 'administer languages',
  label_count: [
    'singular' => '@count content language setting',
    'plural' => '@count content languages settings',
  ],
  list_cache_tags: [
    'rendered',
  ],
  constraints: [
    'ImmutableProperties' => [
      'id',
      'target_entity_type_id',
      'target_bundle',
    ],
  ],
  config_export: [
    'id',
    'target_entity_type_id',
    'target_bundle',
    'default_langcode',
    'language_alterable',
  ],
  )]
class ContentLanguageSettings extends ConfigEntityBase implements ContentLanguageSettingsInterface {

  /**
   * The id. Combination of $target_entity_type_id.$target_bundle.
   *
   * @var string
   */
  protected $id;

  /**
   * The entity type ID (machine name).
   *
   * @var string
   */
  protected $target_entity_type_id;

  /**
   * The bundle (machine name).
   *
   * @var string
   */
  protected $target_bundle;

  /**
   * The default language code.
   *
   * @var string
   */
  protected $default_langcode = LanguageInterface::LANGCODE_SITE_DEFAULT;

  /**
   * Indicates if the language is alterable or not.
   *
   * @var bool
   */
  protected $language_alterable = FALSE;

  /**
   * Constructs a ContentLanguageSettings object.
   *
   * In most cases, Field entities are created via
   * FieldConfig::create($values), where $values is the same
   * parameter as in this constructor.
   *
   * @param array $values
   *   An array of the referring entity bundle with:
   *   - target_entity_type_id: The entity type.
   *   - target_bundle: The bundle.
   *   Other array elements will be used to set the corresponding properties on
   *   the class; see the class property documentation for details.
   * @param string $entity_type
   *   (optional) The entity type ID that is being created. Defaults to
   *   "language_content_settings".
   */
  public function __construct(array $values, $entity_type = 'language_content_settings') {
    if (empty($values['target_entity_type_id'])) {
      throw new ContentLanguageSettingsException('Attempt to create content language settings without a target_entity_type_id.');
    }
    if (empty($values['target_bundle'])) {
      throw new ContentLanguageSettingsException('Attempt to create content language settings without a target_bundle.');
    }
    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->target_entity_type_id . '.' . $this->target_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId() {
    return $this->target_entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle() {
    return $this->target_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetBundle($target_bundle) {
    $this->target_bundle = $target_bundle;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultLangcode($default_langcode) {
    $this->default_langcode = $default_langcode;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultLangcode() {
    return $this->default_langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function setLanguageAlterable($language_alterable) {
    $this->language_alterable = $language_alterable;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isLanguageAlterable() {
    return $this->language_alterable;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    $this->id = $this->id();
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultConfiguration() {
    return (!$this->language_alterable && $this->default_langcode == LanguageInterface::LANGCODE_SITE_DEFAULT);
  }

  /**
   * Loads a content language config entity based on the entity type and bundle.
   *
   * @param string $entity_type_id
   *   ID of the entity type.
   * @param string $bundle
   *   Bundle name.
   *
   * @return $this
   *   The content language config entity if one exists. Otherwise, returns
   *   default values.
   */
  public static function loadByEntityTypeBundle($entity_type_id, $bundle) {
    if ($entity_type_id == NULL || $bundle == NULL) {
      return NULL;
    }
    $config = \Drupal::entityTypeManager()->getStorage('language_content_settings')->load($entity_type_id . '.' . $bundle);
    if ($config == NULL) {
      $config = ContentLanguageSettings::create(['target_entity_type_id' => $entity_type_id, 'target_bundle' => $bundle]);
    }
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Create dependency on the bundle.
    $entity_type = \Drupal::entityTypeManager()->getDefinition($this->target_entity_type_id);
    $bundle_config_dependency = $entity_type->getBundleConfigDependency($this->target_bundle);
    $this->addDependency($bundle_config_dependency['type'], $bundle_config_dependency['name']);

    return $this;
  }

  /**
   * Returns all valid values for the `default_langcode` property.
   *
   * @return string[]
   *   All possible valid default langcodes. This includes all langcodes in the
   *   standard list of human languages, along with special langcodes like
   *   `site_default`, `current_interface` and `authors_default`.
   *
   * @see \Drupal\language\Element\LanguageConfiguration::getDefaultOptions()
   * @see \Drupal\Core\TypedData\Plugin\DataType\LanguageReference::getAllValidLangcodes()
   */
  public static function getAllValidDefaultLangcodes(): array {
    $language_manager = \Drupal::service('language_manager');
    return array_unique([
      ...array_keys($language_manager->getLanguages(LanguageInterface::STATE_ALL)),
      LanguageInterface::LANGCODE_SITE_DEFAULT,
      'current_interface',
      'authors_default',
    ]);
  }

}
