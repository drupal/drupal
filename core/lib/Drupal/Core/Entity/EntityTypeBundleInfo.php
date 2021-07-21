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
  public function getBundleInfo($entity_type_id) {
    $bundle_info = $this->getAllBundleInfo();
    return isset($bundle_info[$entity_type_id]) ? $bundle_info[$entity_type_id] : [];
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
        $config_entity_bundle_labels = [];
        foreach ($this->entityTypeManager->getDefinitions() as $type => $entity_type) {
          // First look for entity types that act as bundles for others, load them
          // and add them as bundles.
          if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
            foreach ($this->entityTypeManager->getStorage($bundle_entity_type)->loadMultiple() as $entity) {
              $this->bundleInfo[$type][$entity->id()]['label'] = $entity->label();
              $config_entity_bundle_labels[$type][$entity->id()] = $entity->label();
            }
          }
          // If entity type bundles are not supported and
          // hook_entity_bundle_info() has not already set up bundle
          // information, use the entity type name and label.
          elseif (!isset($this->bundleInfo[$type])) {
            $this->bundleInfo[$type][$type]['label'] = $entity_type->getLabel();
          }
        }
        $this->moduleHandler->alter('entity_bundle_info', $this->bundleInfo);
        // Check for altered labels of bundles stored as config entities.
        $this->checkConfigEnityBundleAlteredLabels($config_entity_bundle_labels);
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

  /**
   * Checks for altered labels of bundles stored as config entities.
   *
   * @param array $config_entity_bundle_labels
   *   A list of labels of config entity bundles grouped by entity type.
   */
  protected function checkConfigEnityBundleAlteredLabels(array $config_entity_bundle_labels): void {
    // Collect the IDs of all bundles stored as config entities whose labels
    // were altered via hook_entity_bundle_info_alter().
    $altered_label_bundles = [];
    foreach ($config_entity_bundle_labels as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle => $label) {
        $altered_label = $this->bundleInfo[$entity_type_id][$bundle]['label'] ?? NULL;
        if ($altered_label !== $label) {
          $altered_label_bundles[$entity_type_id][] = $bundle;
        }
      }
    }
    if ($altered_label_bundles) {
      $altered_label_bundles_string = trim(
        array_reduce(
          array_keys($altered_label_bundles),
          function (string $string, string $entity_type_id) use ($altered_label_bundles): string {
            return $string . implode(', ', $altered_label_bundles[$entity_type_id]) . " ({$entity_type_id}) ";
          },
          ''
        )
      );
      // @todo Convert deprecation to exception in drupal:10.0.0.
      @trigger_error("Using hook_entity_bundle_info_alter() to alter the label of bundles stored as config entities is deprecated in drupal:9.2.0 and is not permitted in drupal:10.0.0. Label altered bundles: {$altered_label_bundles_string}. Use different methods to alter the label for bundles stored as config entities. See https://www.drupal.org/node/3186694", E_USER_DEPRECATED);
    }
  }
}
