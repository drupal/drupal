<?php

namespace Drupal\rest\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a REST resource annotation object.
 *
 * Plugin Namespace: Plugin\rest\resource
 *
 * For a working example, see \Drupal\dblog\Plugin\rest\resource\DbLogResource
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
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The serialization class to deserialize serialized data into.
   *
   * This property is optional and it does not need to be declared.
   *
   * @var string
   *
   * @see \Symfony\Component\Serializer\SerializerInterface's "type" parameter.
   */
  public $serialization_class;

  /**
   * The URI paths that this REST resource plugin provides.
   *
   * Key-value pairs, with link relation type plugin IDs as keys, and URL
   * templates as values.
   *
   * @var string[]
   *
   * @see core/core.link_relation_types.yml
   */
  public $uri_paths = [];

}
