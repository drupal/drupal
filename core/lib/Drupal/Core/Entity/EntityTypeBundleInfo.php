<?php

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
   * Constructs a new EntityTypeBundleInfo.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typedDataManager
   *   The typed data manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LanguageManagerInterface $languageManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected TypedDataManagerInterface $typedDataManager,
    CacheBackendInterface $cache_backend,
  ) {
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleInfo(string $entity_type_id) {
    $bundle_info = $this->getAllBundleInfo();
    return $bundle_info[$entity_type_id] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleLabels(string $entity_type_id): array {
    return array_map(static fn (array $bundle_info) => $bundle_info['label'], $this->getBundleInfo($entity_type_id));
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
        foreach ($this->entityTypeManager->getDefinitions() as $type => $entity_type) {
          // First look for entity types that act as bundles for others, load
          // them and add them as bundles.
          if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
            foreach ($this->entityTypeManager->getStorage($bundle_entity_type)->loadMultiple() as $entity) {
              $this->bundleInfo[$type][$entity->id()]['label'] = $entity->label();
            }
          }
          // If entity type bundles are not supported and
          // hook_entity_bundle_info() has not already set up bundle
          // information, use the entity type name and label.
          elseif (!isset($this->bundleInfo[$type])) {
            $this->bundleInfo[$type][$type]['label'] = $entity_type->getLabel();
          }

          // Add bundle information provided by entity type plugin discovery
          // using the Drupal\Core\Entity\Attribute\Bundle attribute.
          $attribute_entity_type_bundle_info = $entity_type->get('entity_type_bundle_info');
          if ($attribute_entity_type_bundle_info === NULL) {
            continue;
          }
          foreach ($attribute_entity_type_bundle_info as $bundle => $info) {
            if ($bundle_entity_type && !isset($this->bundleInfo[$type][$bundle])) {
              // If the entity type has a bundle entity type, do not allow
              // bundle definitions to be created by attributes.
              continue;
            }
            $this->bundleInfo[$type][$bundle]['class'] = $info['class'];
            $additional_bundle_info = array_filter([
              'label' => $info['label'],
              'translatable' => $info['translatable'],
            ], fn($property) => $property !== NULL);
            $this->bundleInfo[$type][$bundle] = $additional_bundle_info + $this->bundleInfo[$type][$bundle];

            // Make sure the bundle has a label.
            $this->bundleInfo[$type][$bundle]['label'] ??= $bundle;
          }
        }

        $this->moduleHandler->alter('entity_bundle_info', $this->bundleInfo);
        $this->cacheSet("entity_bundle_info:$langcode", $this->bundleInfo, Cache::PERMANENT, [
          'entity_types',
          'entity_bundles',
        ]);
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
