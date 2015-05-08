<?php

/**
 * @file
 * Contains \Drupal\rest\LinkManager\TypeLinkManager.
 */

namespace Drupal\rest\LinkManager;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class TypeLinkManager extends LinkManagerBase implements TypeLinkManagerInterface {

  /**
   * Injected cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface;
   */
  protected $cache;

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
   *   The injected cache backend for caching type URIs.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(CacheBackendInterface $cache, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->cache = $cache;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->requestStack = $request_stack;
  }

  /**
   * Get a type link for a bundle.
   *
   * @param string $entity_type
   *   The bundle's entity type.
   * @param string $bundle
   *   The name of the bundle.
   * @param array $context
   *   Context of normalizer/serializer.
   *
   * @return string
   *   The URI that identifies this bundle.
   */
  public function getTypeUri($entity_type, $bundle, $context = array()) {
    $uri = $this->getLinkDomain() . "/rest/type/$entity_type/$bundle";
    $this->moduleHandler->alter('rest_type_uri', $uri, $context);
    return $uri;
  }

  /**
   * Implements \Drupal\rest\LinkManager\TypeLinkManagerInterface::getTypeInternalIds().
   */
  public function getTypeInternalIds($type_uri, $context = array()) {
    $types = $this->getTypes($context);
    if (isset($types[$type_uri])) {
      return $types[$type_uri];
    }
    return FALSE;
  }

  /**
   * Get the array of type links.
   *
   * @param array $context
   *   Context from the normalizer/serializer operation.
   *
   * @return array
   *   An array of typed data ids (entity_type and bundle) keyed by
   *   corresponding type URI.
   */
  protected function getTypes($context = array()) {
    $cid = 'rest:links:types';
    $cache = $this->cache->get($cid);
    if (!$cache) {
      $this->writeCache($context);
      $cache = $this->cache->get($cid);
    }
    return $cache->data;
  }

  /**
   * Writes the cache of type links.
   *
   * @param array $context
   *   Context from the normalizer/serializer operation.
   */
  protected function writeCache($context = array()) {
    $data = array();

    // Type URIs correspond to bundles. Iterate through the bundles to get the
    // URI and data for them.
    $entity_types = \Drupal::entityManager()->getDefinitions();
    foreach (entity_get_bundles() as $entity_type_id => $bundles) {
      // Only content entities are supported currently.
      // @todo Consider supporting config entities.
      if ($entity_types[$entity_type_id]->isSubclassOf('\Drupal\Core\Config\Entity\ConfigEntityInterface')) {
        continue;
      }
      foreach ($bundles as $bundle => $bundle_info) {
        // Get a type URI for the bundle.
        $bundle_uri = $this->getTypeUri($entity_type_id, $bundle, $context);
        $data[$bundle_uri] = array(
          'entity_type' => $entity_type_id,
          'bundle' => $bundle,
        );
      }
    }
    // These URIs only change when entity info changes, so cache it permanently
    // and only clear it when entity_info is cleared.
    $this->cache->set('rest:links:types', $data, Cache::PERMANENT, array('entity_types'));
  }

}
