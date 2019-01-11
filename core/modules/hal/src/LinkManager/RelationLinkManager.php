<?php

namespace Drupal\hal\LinkManager;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RelationLinkManager extends LinkManagerBase implements RelationLinkManagerInterface {
  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = ['entityManager' => 'entity.manager'];

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache of relation URIs and their associated Typed Data IDs.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(CacheBackendInterface $cache, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, RequestStack $request_stack, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, EntityFieldManagerInterface $entity_field_manager = NULL) {
    $this->cache = $cache;
    $this->entityTypeManager = $entity_type_manager;
    if (!$entity_type_bundle_info) {
      @trigger_error('The entity_type.bundle.info service must be passed to RelationLinkManager::__construct(), it is required before Drupal 9.0.0. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
      $entity_type_bundle_info = \Drupal::service('entity_type.bundle.info');
    }
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    if (!$entity_field_manager) {
      @trigger_error('The entity_field.manager service must be passed to RelationLinkManager::__construct(), it is required before Drupal 9.0.0. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
      $entity_field_manager = \Drupal::service('entity_field.manager');
    }
    $this->entityFieldManager = $entity_field_manager;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationUri($entity_type, $bundle, $field_name, $context = []) {
    // Per the interface documentation of this method, the returned URI may
    // optionally also serve as the URL of a documentation page about this
    // field. However, Drupal does not currently implement such a documentation
    // page. Therefore, we return a URI assembled relative to the site's base
    // URL, which is sufficient to uniquely identify the site's entity type +
    // bundle + field for use in hypermedia formats, but we do not take into
    // account unclean URLs, language prefixing, or anything else that would be
    // required for Drupal to be able to respond with content at this URL. If a
    // module is installed that adds such content, but requires this URL to be
    // different (e.g., include a language prefix), then the module must also
    // override the RelationLinkManager class/service to return the desired URL.
    $uri = $this->getLinkDomain($context) . "/rest/relation/$entity_type/$bundle/$field_name";
    $this->moduleHandler->alter('hal_relation_uri', $uri, $context);
    $this->moduleHandler->alterDeprecated('This hook is deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.0. Implement hook_hal_relation_uri_alter() instead.', 'rest_relation_uri', $uri, $context);
    return $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationInternalIds($relation_uri, $context = []) {
    $relations = $this->getRelations($context);
    if (isset($relations[$relation_uri])) {
      return $relations[$relation_uri];
    }
    return FALSE;
  }

  /**
   * Get the array of relation links.
   *
   * Any field can be handled as a relation simply by changing how it is
   * normalized. Therefore, there is no prior knowledge that can be used here
   * to determine which fields to assign relation URIs. Instead, each field,
   * even primitives, are given a relation URI. It is up to the caller to
   * determine which URIs to use.
   *
   * @param array $context
   *   Context from the normalizer/serializer operation.
   *
   * @return array
   *   An array of typed data IDs keyed by corresponding relation URI. The keys
   *   are:
   *   - 'entity_type_id'
   *   - 'bundle'
   *   - 'field_name'
   *   - 'entity_type' (deprecated)
   *   The values for 'entity_type_id', 'bundle' and 'field_name' are strings.
   *   The 'entity_type' key exists for backwards compatibility and its value is
   *   the full entity type object. The 'entity_type' key will be removed before
   *   Drupal 9.0.
   *
   * @see https://www.drupal.org/node/2877608
   */
  protected function getRelations($context = []) {
    $cid = 'hal:links:relations';
    $cache = $this->cache->get($cid);
    if (!$cache) {
      $data = $this->writeCache($context);
    }
    else {
      $data = $cache->data;
    }

    // @todo https://www.drupal.org/node/2716163 Remove this in Drupal 9.0.
    foreach ($data as $relation_uri => $ids) {
      $data[$relation_uri]['entity_type'] = $this->entityTypeManager->getDefinition($ids['entity_type_id']);
    }
    return $data;
  }

  /**
   * Writes the cache of relation links.
   *
   * @param array $context
   *   Context from the normalizer/serializer operation.
   *
   * @return array
   *   An array of typed data IDs keyed by corresponding relation URI. The keys
   *   are:
   *   - 'entity_type_id'
   *   - 'bundle'
   *   - 'field_name'
   *   The values for 'entity_type_id', 'bundle' and 'field_name' are strings.
   */
  protected function writeCache($context = []) {
    $data = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        foreach ($this->entityTypeBundleInfo->getBundleInfo($entity_type->id()) as $bundle => $bundle_info) {
          foreach ($this->entityFieldManager->getFieldDefinitions($entity_type->id(), $bundle) as $field_definition) {
            $relation_uri = $this->getRelationUri($entity_type->id(), $bundle, $field_definition->getName(), $context);
            $data[$relation_uri] = [
              'entity_type_id' => $entity_type->id(),
              'bundle' => $bundle,
              'field_name' => $field_definition->getName(),
            ];
          }
        }
      }
    }
    // These URIs only change when field info changes, so cache it permanently
    // and only clear it when the fields cache is cleared.
    $this->cache->set('hal:links:relations', $data, Cache::PERMANENT, ['entity_field_info']);
    return $data;
  }

}
