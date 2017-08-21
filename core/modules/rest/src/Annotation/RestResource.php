<?php

namespace Drupal\rest\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a REST resource annotation object.
 *
 * Plugin Namespace: Plugin\rest\resource
 *
 * For a working example, see \Drupal\dblog\Plugin\rest\resource\DBLogResource
 *
 * @see \Drupal\rest\Plugin\Type\ResourcePluginManager
 * @see \Drupal\rest\Plugin\ResourceBase
 * @see \Drupal\rest\Plugin\ResourceInterface
 * @see plugin_api
 *
 * @ingroup third_party
 *
 * @Annotation
 */
class RestResource extends Plugin {

  /**
   * The REST resource plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the REST resource plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The serialization class to deserialize serialized data into.
   *
   * @see \Symfony\Component\Serializer\SerializerInterface's "type" parameter.
   *
   * @var string (optional)
   */
  public $serialization_class;

  /**
   * The URI paths that this REST resource plugin provides.
   *
   * Key-value pairs, with link relation type plugin IDs as keys, and URL
   * templates as values.
   *
   * @see core/core.link_relation_types.yml
   *
   * @var string[]
   */
  public $uri_paths = [];

}
