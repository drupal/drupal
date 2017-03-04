<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a repository for entity display objects (view modes and form modes).
 */
class EntityDisplayRepository implements EntityDisplayRepositoryInterface {

  use UseCacheBackendTrait;
  use StringTranslationTrait;

  /**
   * Static cache of display modes information.
   *
   * @var array
   */
  protected $displayModeInfo = [];

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new EntityDisplayRepository.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend, LanguageManagerInterface $language_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->cacheBackend = $cache_backend;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllViewModes() {
    return $this->getAllDisplayModesByEntityType('view_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModes($entity_type_id) {
    return $this->getDisplayModesByEntityType('view_mode', $entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllFormModes() {
    return $this->getAllDisplayModesByEntityType('form_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModes($entity_type_id) {
    return $this->getDisplayModesByEntityType('form_mode', $entity_type_id);
  }

  /**
   * Gets the entity display mode info for all entity types.
   *
   * @param string $display_type
   *   The display type to be retrieved. It can be "view_mode" or "form_mode".
   *
   * @return array
   *   The display mode info for all entity types.
   */
  protected function getAllDisplayModesByEntityType($display_type) {
    if (!isset($this->displayModeInfo[$display_type])) {
      $key = 'entity_' . $display_type . '_info';
      $entity_type_id = 'entity_' . $display_type;
      $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_INTERFACE)->getId();
      if ($cache = $this->cacheGet("$key:$langcode")) {
        $this->displayModeInfo[$display_type] = $cache->data;
      }
      else {
        $this->displayModeInfo[$display_type] = [];
        foreach ($this->entityTypeManager->getStorage($entity_type_id)->loadMultiple() as $display_mode) {
          list($display_mode_entity_type, $display_mode_name) = explode('.', $display_mode->id(), 2);
          $this->displayModeInfo[$display_type][$display_mode_entity_type][$display_mode_name] = $display_mode->toArray();
        }
        $this->moduleHandler->alter($key, $this->displayModeInfo[$display_type]);
        $this->cacheSet("$key:$langcode", $this->displayModeInfo[$display_type], CacheBackendInterface::CACHE_PERMANENT, ['entity_types', 'entity_field_info']);
      }
    }

    return $this->displayModeInfo[$display_type];
  }

  /**
   * Gets the entity display mode info for a specific entity type.
   *
   * @param string $display_type
   *   The display type to be retrieved. It can be "view_mode" or "form_mode".
   * @param string $entity_type_id
   *   The entity type whose display mode info should be returned.
   *
   * @return array
   *   The display mode info for a specific entity type.
   */
  protected function getDisplayModesByEntityType($display_type, $entity_type_id) {
    if (isset($this->displayModeInfo[$display_type][$entity_type_id])) {
      return $this->displayModeInfo[$display_type][$entity_type_id];
    }
    else {
      $display_modes = $this->getAllDisplayModesByEntityType($display_type);
      if (isset($display_modes[$entity_type_id])) {
        return $display_modes[$entity_type_id];
      }
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModeOptions($entity_type) {
    return $this->getDisplayModeOptions('view_mode', $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModeOptions($entity_type_id) {
    return $this->getDisplayModeOptions('form_mode', $entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModeOptionsByBundle($entity_type_id, $bundle) {
    return $this->getDisplayModeOptionsByBundle('view_mode', $entity_type_id, $bundle);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModeOptionsByBundle($entity_type_id, $bundle) {
    return $this->getDisplayModeOptionsByBundle('form_mode', $entity_type_id, $bundle);
  }

  /**
   * Gets an array of display mode options.
   *
   * @param string $display_type
   *   The display type to be retrieved. It can be "view_mode" or "form_mode".
   * @param string $entity_type_id
   *   The entity type whose display mode options should be returned.
   *
   * @return array
   *   An array of display mode labels, keyed by the display mode ID.
   */
  protected function getDisplayModeOptions($display_type, $entity_type_id) {
    $options = ['default' => t('Default')];
    foreach ($this->getDisplayModesByEntityType($display_type, $entity_type_id) as $mode => $settings) {
      $options[$mode] = $settings['label'];
    }
    return $options;
  }

  /**
   * Returns an array of enabled display mode options by bundle.
   *
   * @param $display_type
   *   The display type to be retrieved. It can be "view_mode" or "form_mode".
   * @param string $entity_type_id
   *   The entity type whose display mode options should be returned.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   An array of display mode labels, keyed by the display mode ID.
   */
  protected function getDisplayModeOptionsByBundle($display_type, $entity_type_id, $bundle) {
    // Collect all the entity's display modes.
    $options = $this->getDisplayModeOptions($display_type, $entity_type_id);

    // Filter out modes for which the entity display is disabled
    // (or non-existent).
    $load_ids = [];
    // Get the list of available entity displays for the current bundle.
    foreach (array_keys($options) as $mode) {
      $load_ids[] = $entity_type_id . '.' . $bundle . '.' . $mode;
    }

    // Load the corresponding displays.
    $displays = $this->entityTypeManager
      ->getStorage($display_type == 'form_mode' ? 'entity_form_display' : 'entity_view_display')
      ->loadMultiple($load_ids);

    // Unset the display modes that are not active or do not exist.
    foreach (array_keys($options) as $mode) {
      $display_id = $entity_type_id . '.' . $bundle . '.' . $mode;
      if (!isset($displays[$display_id]) || !$displays[$display_id]->status()) {
        unset($options[$mode]);
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function clearDisplayModeInfo() {
    $this->displayModeInfo = [];
    return $this;
  }

}
