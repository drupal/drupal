<?php

/**
 * @file
 * Contains \Drupal\rest\LinkManager\RelationLinkManager.
 */

namespace Drupal\rest\LinkManager;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;

class RelationLinkManager implements RelationLinkManagerInterface {

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
   * The unrouted URL assembler.
   *
   * @var \Drupal\Core\Utility\UnroutedUrlAssemblerInterface
   */
  protected $urlAssembler;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache of relation URIs and their associated Typed Data IDs.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Utility\UnroutedUrlAssemblerInterface $url_assembler
   *   The unrouted URL assembler.
   */
  public function __construct(CacheBackendInterface $cache, EntityManagerInterface $entity_manager, UnroutedUrlAssemblerInterface $url_assembler) {
    $this->cache = $cache;
    $this->entityManager = $entity_manager;
    $this->urlAssembler = $url_assembler;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationUri($entity_type, $bundle, $field_name) {
    return $this->urlAssembler->assemble("base:rest/relation/$entity_type/$bundle/$field_name", array('absolute' => TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationInternalIds($relation_uri) {
    $relations = $this->getRelations();
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
   * @return array
   *   An array of typed data ids (entity_type, bundle, and field name) keyed
   *   by corresponding relation URI.
   */
  protected function getRelations() {
    $cid = 'rest:links:relations';
    $cache = $this->cache->get($cid);
    if (!$cache) {
      $this->writeCache();
      $cache = $this->cache->get($cid);
    }
    return $cache->data;
  }

  /**
   * Writes the cache of relation links.
   */
  protected function writeCache() {
    $data = array();

    foreach ($this->entityManager->getDefinitions() as $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        foreach ($this->entityManager->getBundleInfo($entity_type->id()) as $bundle => $bundle_info) {
          foreach ($this->entityManager->getFieldDefinitions($entity_type->id(), $bundle) as $field_definition) {
            $relation_uri = $this->getRelationUri($entity_type->id(), $bundle, $field_definition->getName());
            $data[$relation_uri] = array(
              'entity_type' => $entity_type,
              'bundle' => $bundle,
              'field_name' => $field_definition->getName(),
            );
          }
        }
      }
    }
    // These URIs only change when field info changes, so cache it permanently
    // and only clear it when the fields cache is cleared.
    $this->cache->set('rest:links:relations', $data, Cache::PERMANENT, array('entity_field_info'));
  }
}
