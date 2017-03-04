<?php

namespace Drupal\hal\LinkManager;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RelationLinkManager extends LinkManagerBase implements RelationLinkManagerInterface {

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface;
   */
  protected $cache;

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(CacheBackendInterface $cache, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->cache = $cache;
    $this->entityManager = $entity_manager;
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
    $uri = $this->getLinkDomain() . "/rest/relation/$entity_type/$bundle/$field_name";
    $this->moduleHandler->alter('hal_relation_uri', $uri, $context);
    // @deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.0. This
    // hook is invoked to maintain backwards compatibility
    $this->moduleHandler->alter('rest_relation_uri', $uri, $context);
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
   *   An array of typed data ids (entity_type, bundle, and field name) keyed
   *   by corresponding relation URI.
   */
  protected function getRelations($context = []) {
    $cid = 'hal:links:relations';
    $cache = $this->cache->get($cid);
    if (!$cache) {
      $this->writeCache($context);
      $cache = $this->cache->get($cid);
    }
    return $cache->data;
  }

  /**
   * Writes the cache of relation links.
   *
   * @param array $context
   *   Context from the normalizer/serializer operation.
   */
  protected function writeCache($context = []) {
    $data = [];

    foreach ($this->entityManager->getDefinitions() as $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        foreach ($this->entityManager->getBundleInfo($entity_type->id()) as $bundle => $bundle_info) {
          foreach ($this->entityManager->getFieldDefinitions($entity_type->id(), $bundle) as $field_definition) {
            $relation_uri = $this->getRelationUri($entity_type->id(), $bundle, $field_definition->getName(), $context);
            $data[$relation_uri] = [
              'entity_type' => $entity_type,
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
  }

}
