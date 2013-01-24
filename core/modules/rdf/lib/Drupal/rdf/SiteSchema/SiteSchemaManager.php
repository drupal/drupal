<?php

/**
 * @file
 * Contains SiteSchemaManager.
 */

namespace Drupal\rdf\SiteSchema;

use ReflectionClass;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\rdf\SiteSchema\SiteSchema;
use Drupal\rdf\SiteSchema\BundleSchema;

class SiteSchemaManager {

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface;
   */
  protected $cache;

  /**
   * Constructor.
   */
  public function __construct(CacheBackendInterface $cache) {
    $this->cache = $cache;
    $this->siteSchemas = array(
      SiteSchema::CONTENT_DEPLOYMENT => new SiteSchema(SiteSchema::CONTENT_DEPLOYMENT),
      SiteSchema::SYNDICATION => new SiteSchema(SiteSchema::SYNDICATION),
    );
  }

  /**
   * Writes the cache of site schema types.
   */
  public function writeCache() {
    $data = array();

    // Type URIs correspond to bundles. Iterate through the bundles to get the
    // URI and data for them.
    $entity_info = entity_get_info();
    foreach (entity_get_bundles() as $entity_type => $bundles) {
      // Only content entities are supported currently.
      // @todo Consider supporting config entities.
      $entity_type_info = $entity_info[$entity_type];
      $reflection = new ReflectionClass($entity_type_info['class']);
      if ($reflection->implementsInterface('\Drupal\Core\Config\Entity\ConfigEntityInterface')) {
        continue;
      }
      foreach ($bundles as $bundle => $bundle_info) {
        // Get a type URI for the bundle in each of the defined schemas.
        foreach ($this->siteSchemas as $schema) {
          $bundle_uri = $schema->bundle($entity_type, $bundle)->getUri();
          $data[$bundle_uri] = array(
            'entity_type' => $entity_type,
            'bundle' => $bundle,
          );
        }
      }
    }
    // These URIs only change when entity info changes, so cache it permanently
    // and only clear it when entity_info is cleared.
    $this->cache->set('rdf:site_schema:types', $data, CacheBackendInterface::CACHE_PERMANENT, array('entity_info' => TRUE));
  }

  public function getSchemas() {
    return $this->siteSchemas;
  }

  public function getSchema($schema_path) {
    return $this->siteSchemas[$schema_path];
  }

  /**
   * Get the array of site schema types.
   *
   * @return array
   *   An array of typed data ids (entity_type and bundle) keyed by
   *   corresponding site schema URI.
   */
  public function getTypes() {
    $cid = 'rdf:site_schema:types';
    $cache = $this->cache->get($cid);
    if (!$cache) {
      $this->writeCache();
      $cache = $this->cache->get($cid);
    }
    return $cache->data;
  }

}
