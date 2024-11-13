<?php

namespace Drupal\Core\Entity\Attribute;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a configuration entity type attribute object.
 *
 * Configuration entity type plugins use an object-based attribute method. The
 * attribute properties of configuration entity types are found on
 * \Drupal\Core\Config\Entity\ConfigEntityType and are accessed using get/set
 * methods defined in \Drupal\Core\Entity\EntityTypeInterface.
 *
 * @ingroup entity_api
 *
 * @see \Drupal\Core\Entity\EntityType
 * @see \Drupal\Core\Config\Entity\ConfigEntityType
 * @see \Drupal\Core\Config\Entity\ConfigEntityTypeInterface
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ConfigEntityType extends EntityType {

  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?TranslatableMarkup $label_collection = NULL,
    public readonly ?TranslatableMarkup $label_singular = NULL,
    public readonly ?TranslatableMarkup $label_plural = NULL,
    public readonly ?string $config_prefix = NULL,
    public readonly string $entity_type_class = 'Drupal\Core\Config\Entity\ConfigEntityType',
    public readonly string $group = 'configuration',
    public readonly TranslatableMarkup $group_label = new TranslatableMarkup('Configuration', [], ['context' => 'Entity type group']),
    public readonly bool $static_cache = FALSE,
    public readonly bool $persistent_cache = TRUE,
    protected readonly array $entity_keys = [],
    protected readonly array $handlers = [],
    protected readonly array $links = [],
    public readonly ?string $admin_permission = NULL,
    public readonly ?string $collection_permission = NULL,
    public readonly string $permission_granularity = 'entity_type',
    public readonly ?string $bundle_of = NULL,
    public readonly ?TranslatableMarkup $bundle_label = NULL,
    public readonly bool $internal = FALSE,
    public readonly array $label_count = [],
    public readonly ?string $uri_callback = NULL,
    public readonly bool $common_reference_target = FALSE,
    public readonly array $list_cache_contexts = [],
    public readonly array $list_cache_tags = [],
    public readonly array $constraints = [],
    public readonly array $additional = [],
    public readonly array $lookup_keys = [],
    public readonly array $config_export = [],
  ) {
  }

}
