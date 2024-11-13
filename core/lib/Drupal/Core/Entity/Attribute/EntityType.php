<?php

namespace Drupal\Core\Entity\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an entity type for plugin discovery.
 *
 * Entity type plugins use an object-based attribute method. The attribute
 * properties of entity types are found on \Drupal\Core\Entity\EntityType and
 * are accessed using get/set methods defined in
 * \Drupal\Core\Entity\EntityTypeInterface.
 *
 * @ingroup entity_api
 *
 * @see \Drupal\Core\Entity\EntityType
 * @see \Drupal\Core\Entity\ContentEntityTypeInterface
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class EntityType extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?TranslatableMarkup $label_collection = NULL,
    public readonly ?TranslatableMarkup $label_singular = NULL,
    public readonly ?TranslatableMarkup $label_plural = NULL,
    public readonly string $entity_type_class = 'Drupal\Core\Entity\EntityType',
    public readonly string $group = 'default',
    public readonly TranslatableMarkup $group_label = new TranslatableMarkup('Other', [], ['context' => 'Entity type group']),
    public readonly bool $static_cache = TRUE,
    public readonly bool $render_cache = TRUE,
    public readonly bool $persistent_cache = TRUE,
    protected readonly array $entity_keys = [],
    protected readonly array $handlers = [],
    protected readonly array $links = [],
    public readonly ?string $admin_permission = NULL,
    public readonly ?string $collection_permission = NULL,
    public readonly string $permission_granularity = 'entity_type',
    public readonly ?string $bundle_entity_type = NULL,
    public readonly ?string $bundle_of = NULL,
    public readonly ?TranslatableMarkup $bundle_label = NULL,
    public readonly ?string $base_table = NULL,
    public readonly ?string $data_table = NULL,
    public readonly ?string $revision_table = NULL,
    public readonly ?string $revision_data_table = NULL,
    public readonly bool $internal = FALSE,
    public readonly bool $translatable = FALSE,
    public readonly bool $show_revision_ui = FALSE,
    public readonly array $label_count = [],
    public readonly ?string $uri_callback = NULL,
    public readonly ?string $field_ui_base_route = NULL,
    public readonly bool $common_reference_target = FALSE,
    public readonly array $list_cache_contexts = [],
    public readonly array $list_cache_tags = [],
    public readonly array $constraints = [],
    public readonly array $additional = [],
  ) {

  }

  /**
   * {@inheritdoc}
   */
  public function get(): array|object {
    // Use the specified entity type class, and remove it before instantiating.
    $class = $this->entity_type_class;

    $values = array_filter(get_object_vars($this) + [
      'class' => $this->getClass(),
      'provider' => $this->getProvider(),
    ], function ($value, $key) {
      return !($value === NULL && ($key === 'deriver' || $key === 'provider' || $key == 'entity_type_class'));
    }, ARRAY_FILTER_USE_BOTH);

    return new $class($values);
  }

}
