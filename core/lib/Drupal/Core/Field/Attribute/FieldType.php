<?php

declare(strict_types=1);

namespace Drupal\Core\Field\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a FieldType attribute.
 *
 * Additional attribute keys for field types can be defined in
 * hook_field_info_alter().
 *
 * @ingroup field_types
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class FieldType extends Plugin {

  /**
   * Constructs a FieldType attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the field type.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|array|null $description
   *   (optional) A short human-readable description for the field type.
   * @param string $category
   *   (optional) The category under which the field type should be listed in
   *   the UI.
   * @param int $weight
   *   (optional) The weight of the field type.
   * @param string|null $default_widget
   *   (optional) The plugin_id of the default widget for this field type.
   *   This widget must be available whenever the field type is available (i.e.
   *   provided by the field type module, or by a module the field type module
   *   depends on).
   * @param string|null $default_formatter
   *   (optional) The plugin_id of the default formatter for this field type.
   *   This formatter must be available whenever the field type is available
   *   (i.e. provided by the field type module, or by a module the field type
   *   module depends on).
   * @param bool $no_ui
   *   (optional) A boolean stating that fields of this type cannot be created
   *   through the UI.
   * @param string|null $list_class
   *   (optional) The typed data class used for wrapping multiple data items of
   *   the type. Must implement the \Drupal\Core\TypedData\ListInterface.
   * @param int|null $cardinality
   *   (optional) An integer defining a fixed cardinality for this field type.
   *   If this value is not set, cardinality can be configured in the field UI.
   * @param array $constraints
   *   (optional) An array of validation constraints for this type.
   * @param array $config_dependencies
   *   (optional) An array of configuration dependencies.
   * @param array $column_groups
   *   (optional) An array of column groups for the field type.
   * @param array $serialized_property_names
   *   (optional) An array of property names that should be serialized.
   * @param string|null $deriver
   *   (optional) The deriver class for the data type.
   * @param string|null $module
   *   The name of the module providing the field type plugin.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly TranslatableMarkup|array|null $description = NULL,
    public readonly string $category = '',
    public readonly int $weight = 0,
    public readonly ?string $default_widget = NULL,
    public readonly ?string $default_formatter = NULL,
    public readonly bool $no_ui = FALSE,
    public readonly ?string $list_class = NULL,
    public readonly ?int $cardinality = NULL,
    public readonly array $constraints = [],
    public readonly array $config_dependencies = [],
    public readonly array $column_groups = [],
    public readonly array $serialized_property_names = [],
    public readonly ?string $deriver = NULL,
    public readonly ?string $module = NULL,
  ) {
  }

}
