<?php

namespace Drupal\Core\Field\Annotation;

use Drupal\Core\TypedData\Annotation\DataType;

/**
 * Defines a FieldType annotation object.
 *
 * Additional annotation keys for field types can be defined in
 * hook_field_info_alter().
 *
 * @ingroup field_types
 *
 * @Annotation
 */
class FieldType extends DataType {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the module providing the field type plugin.
   *
   * @var string
   */
  public $module;

  /**
   * The human-readable name of the field type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short human readable description for the field type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The category under which the field type should be listed in the UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category = '';

  /**
   * The weight of the field type.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * The plugin ID of the default widget for this field type.
   *
   * This widget must be available whenever the field type is available (i.e.
   * provided by the field type module, or by a module the field type module
   * depends on).
   *
   * @var string
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
  public $default_widget;

  /**
   * The plugin ID of the default formatter for this field type.
   *
   * This formatter must be available whenever the field type is available (i.e.
   * provided by the field type module, or by a module the field type module
   * depends on).
   *
   * @var string
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
  public $default_formatter;

  /**
   * A boolean stating that fields of this type cannot be created through the UI.
   *
   * @var bool
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
  public $no_ui = FALSE;

  /**
   * {@inheritdoc}
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
  public $list_class;

  /**
   * An integer defining a fixed cardinality for this field type.
   *
   * If this value is not set, cardinality can be configured in the field UI.
   *
   * @var int|null
   */
  public $cardinality;

}
