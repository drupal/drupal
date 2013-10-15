<?php

/**
 * @file
 * Contains \Drupal\serialization\EntityResolver\EntityResolverInterface
 */

namespace Drupal\serialization\EntityResolver;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

interface EntityResolverInterface {

  /**
   * Returns the local ID of an entity referenced by serialized data.
   *
   * Drupal entities are loaded by and internally referenced by a local ID.
   * Because different websites can use the same local ID to refer to different
   * entities (e.g., node "1" can be a different node on foo.com and bar.com, or
   * on example.com and staging.example.com), it is generally unsuitable for use
   * in hypermedia data exchanges. Instead, UUIDs, URIs, or other globally
   * unique IDs are preferred.
   *
   * This function takes a $data array representing partially deserialized data
   * for an entity reference, and resolves it to a local entity ID. For example,
   * depending on the data specification being used, $data might contain a
   * 'uuid' key, a 'uri' key, a 'href' key, or some other data identifying the
   * entity, and it is up to the implementor of this interface to resolve that
   * appropriately for the specification being used.
   *
   * @param \Symfony\Component\Serializer\Normalizer\NormalizerInterface $normalizer
   *   The Normalizer which is handling the data.
   * @param array $data
   *   The data passed into the calling Normalizer.
   * @param string $entity_type
   *   The type of entity being resolved; e.g., 'node' or 'user'.
   *
   * @return string|null
   *   Returns the local entity ID, if found. Otherwise, returns NULL.
   */
  public function resolve(NormalizerInterface $normalizer, $data, $entity_type);

}
