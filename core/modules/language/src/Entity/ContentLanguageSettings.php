<?php

/**
 * @file
 * Contains \Drupal\language\Entity\ContentLanguageSettings.
 */

namespace Drupal\language\Entity;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\ContentLanguageSettingsException;
use Drupal\language\ContentLanguageSettingsInterface;

/**
 * Defines the ContentLanguageSettings entity.
 *
 * @ConfigEntityType(
 *   id = "language_content_settings",
 *   label = @Translation("Content Language Settings"),
 *   admin_permission = "administer languages",
 *   config_prefix = "content_settings",
 *   entity_keys = {
 *     "id" = "id"
 *   },
 * )
 */
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
   * entity_create('field_config', $values), where $values is the same
   * parameter as in this constructor.
   *
   * @param array $values
   *   An array of the referring entity bundle with:
   *   - target_entity_type_id: The entity type.
   *   - target_bundle: The bundle.
   *   Other array elements will be used to set the corresponding properties on
   *   the class; see the class property documentation for details.
   *
   * @see entity_create()
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
    $config = \Drupal::entityManager()->getStorage('language_content_settings')->load($entity_type_id . '.' . $bundle);
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
    $bundle_entity_type_id = $this->entityManager()->getDefinition($this->target_entity_type_id)->getBundleEntityType();
    if ($bundle_entity_type_id != 'bundle') {
      // If the target entity type uses entities to manage its bundles then
      // depend on the bundle entity.
      if (!$bundle_entity = $this->entityManager()->getStorage($bundle_entity_type_id)->load($this->target_bundle)) {
        throw new \LogicException(SafeMarkup::format('Missing bundle entity, entity type %type, entity id %bundle.', array('%type' => $bundle_entity_type_id, '%bundle' => $this->target_bundle)));
      }
      $this->addDependency('config', $bundle_entity->getConfigDependencyName());
    }
    return $this->dependencies;
  }

}
