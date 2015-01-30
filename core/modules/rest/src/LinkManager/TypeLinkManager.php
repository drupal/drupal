<?php

/**
 * @file
 * Contains \Drupal\rest\LinkManager\TypeLinkManager.
 */

namespace Drupal\rest\LinkManager;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;

class TypeLinkManager implements TypeLinkManagerInterface {

  /**
   * Injected cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface;
   */
  protected $cache;

  /**
   * The unrouted URL assembler.
   *
   * @var \Drupal\Core\Utility\UnroutedUrlAssemblerInterface
   */
  protected $urlAssembler;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The injected cache backend for caching type URIs.
   * @param \Drupal\Core\Utility\UnroutedUrlAssemblerInterface $url_assembler
   *   The unrouted URL assembler.
   */
  public function __construct(CacheBackendInterface $cache, UnroutedUrlAssemblerInterface $url_assembler) {
    $this->cache = $cache;
    $this->urlAssembler = $url_assembler;
  }

  /**
   * Get a type link for a bundle.
   *
   * @param string $entity_type
   *   The bundle's entity type.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   The URI that identifies this bundle.
   */
  public function getTypeUri($entity_type, $bundle) {
    // @todo Make the base path configurable.
    return $this->urlAssembler->assemble("base:rest/type/$entity_type/$bundle", array('absolute' => TRUE));
  }

  /**
   * Implements \Drupal\rest\LinkManager\TypeLinkManagerInterface::getTypeInternalIds().
   */
  public function getTypeInternalIds($type_uri) {
    $types = $this->getTypes();
    if (isset($types[$type_uri])) {
      return $types[$type_uri];
    }
    return FALSE;
  }

  /**
   * Get the array of type links.
   *
   * @return array
   *   An array of typed data ids (entity_type and bundle) keyed by
   *   corresponding type URI.
   */
  protected function getTypes() {
    $cid = 'rest:links:types';
    $cache = $this->cache->get($cid);
    if (!$cache) {
      $this->writeCache();
      $cache = $this->cache->get($cid);
    }
    return $cache->data;
  }

  /**
   * Writes the cache of type links.
   */
  protected function writeCache() {
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
        $bundle_uri = $this->getTypeUri($entity_type_id, $bundle);
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
