<?php

declare(strict_types=1);

namespace Drupal\rest\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a REST resource attribute object.
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
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class RestResource extends Plugin {

  /**
   * Constructs a RestResource attribute.
   *
   * @param string $id
   *   The REST resource plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the REST resource plugin.
   * @param string|null $serialization_class
   *   (optional) The serialization class to deserialize serialized data into.
   * @param class-string|null $deriver
   *   (optional) The deriver class for the rest resource.
   * @param array $uri_paths
   *   (optional) The URI paths that this REST resource plugin provides.
   *   - key: The link relation type plugin ID.
   *   - value: The URL template.
   *
   * @see \Symfony\Component\Serializer\SerializerInterface
   * @see core/core.link_relation_types.yml
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?string $serialization_class = NULL,
    public readonly ?string $deriver = NULL,
    public readonly array $uri_paths = [],
  ) {}

}
