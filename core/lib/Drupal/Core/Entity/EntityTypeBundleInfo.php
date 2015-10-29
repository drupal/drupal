<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityTypeBundleInfo.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;

/**
 * Provides discovery and retrieval of entity type bundles.
 */
class EntityTypeBundleInfo implements EntityTypeBundleInfoInterface {

  use UseCacheBackendTrait;

  /**
   * Static cache of bundle information.
   *
   * @var array
   */
  protected $bundleInfo;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityTypeBundleInfo.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager
   *   The typed data manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler, TypedDataManagerInterface $typed_data_manager, CacheBackendInterface $cache_backend) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
    $this->typedDataManager = $typed_data_manager;
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleInfo($entity_type) {
    $bundle_info = $this->getAllBundleInfo();
    return isset($bundle_info[$entity_type]) ? $bundle_info[$entity_type] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAllBundleInfo() {
    if (empty($this->bundleInfo)) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
      if ($cache = $this->cacheGet("entity_bundle_info:$langcode")) {
        $this->bundleInfo = $cache->data;
      }
      else {
        $this->bundleInfo = $this->moduleHandler->invokeAll('entity_bundle_info');
        // First look for entity types that act as bundles for others, load them
        // and add them as bundles.
        foreach ($this->entityTypeManager->getDefinitions() as $type => $entity_type) {
          if ($entity_type->getBundleOf()) {
            foreach ($this->entityTypeManager->getStorage($type)->loadMultiple() as $entity) {
              $this->bundleInfo[$entity_type->getBundleOf()][$entity->id()]['label'] = $entity->label();
            }
          }
        }
        foreach ($this->entityTypeManager->getDefinitions() as $type => $entity_type) {
          // If no bundles are provided, use the entity type name and label.
          if (!isset($this->bundleInfo[$type])) {
            $this->bundleInfo[$type][$type]['label'] = $entity_type->getLabel();
          }
        }
        $this->moduleHandler->alter('entity_bundle_info', $this->bundleInfo);
        $this->cacheSet("entity_bundle_info:$langcode", $this->bundleInfo, Cache::PERMANENT, ['entity_types', 'entity_bundles']);
      }
    }

    return $this->bundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedBundles() {
    $this->bundleInfo = [];
    Cache::invalidateTags(['entity_bundles']);
    // Entity bundles are exposed as data types, clear that cache too.
    $this->typedDataManager->clearCachedDefinitions();
  }

}
