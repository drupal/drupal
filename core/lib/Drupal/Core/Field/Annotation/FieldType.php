<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Annotation\FieldType.
 */

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
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A short human readable description for the field type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * The category under which the field type should be listed in the UI.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $category = '';

  /**
   * The plugin_id of the default widget for this field type.
   *
   * This widget must be available whenever the field type is available (i.e.
   * provided by the field type module, or by a module the field type module
   * depends on).
   *
   * @var string
   */
  public $default_widget;

  /**
   * The plugin_id of the default formatter for this field type.
   *
   * This formatter must be available whenever the field type is available (i.e.
   * provided by the field type module, or by a module the field type module
   * depends on).
   *
   * @var string
   */
  public $default_formatter;

  /**
   * A boolean stating that fields of this type cannot be created through the UI.
   *
   * @var bool
   */
  public $no_ui = FALSE;

  /**
   * {@inheritdoc}
   */
  public $list_class;

}
