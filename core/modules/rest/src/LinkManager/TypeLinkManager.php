<?php

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
   * {@inheritdoc}
   */
  public function getTypeUri($entity_type, $bundle, $context = array()) {
    // Per the interface documention of this method, the returned URI may
    // optionally also serve as the URL of a documentation page about this
    // bundle. However, the REST module does not currently implement such
    // a documentation page. Therefore, we return a URI assembled relative to
    // the site's base URL, which is sufficient to uniquely identify the site's
    // entity type and bundle for use in hypermedia formats, but we do not
    // take into account unclean URLs, language prefixing, or anything else
    // that would be required for Drupal to be able to respond with content
    // at this URL. If a module is installed that adds such content, but
    // requires this URL to be different (e.g., include a language prefix),
    // then the module must also override the TypeLinkManager class/service to
    // return the desired URL.
    $uri = $this->getLinkDomain() . "/rest/type/$entity_type/$bundle";
    $this->moduleHandler->alter('rest_type_uri', $uri, $context);
    return $uri;
  }

  /**
   * {@inheritdoc}
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
      $data = $this->writeCache($context);
    }
    else {
      $data = $cache->data;
    }
    return $data;
  }

  /**
   * Writes the cache of type links.
   *
   * @param array $context
   *   Context from the normalizer/serializer operation.
   *
   * @return array
   *   An array of typed data ids (entity_type and bundle) keyed by
   *   corresponding type URI.
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
    return $data;
  }

}
