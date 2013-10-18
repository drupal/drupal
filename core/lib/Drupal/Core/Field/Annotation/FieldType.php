<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Annotation\FieldType.
 */

namespace Drupal\Core\Field\Annotation;

use Drupal\Core\TypedData\Annotation\DataType;

/**
 * Defines a FieldType annotation object.
 *
 * Additional annotation keys for field types can be defined in
 * hook_field_info_alter().
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
   * An array of field-level settings available for the field type.
   *
   * Keys are the names of the settings, and values are the default values for
   * those settings.
   *
   * @var array
   */
  public $settings;

  /**
   * An array of instance-level settings available for the field type.
   *
   * Keys are the names of the settings, and values are the default values for
   * those settings.
   *
   * Instance-level settings can have different values on each field instance,
   * and thus allow greater flexibility than field-level settings. It is
   * recommended to put settings at the instance level whenever possible.
   * Notable exceptions: settings acting on the storage schema, or settings that
   * Views needs to use across field instances (for example, settings defining
   * the list of allowed values for the field).
   *
   * @var array
   */
  public $instance_settings;

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
   * A boolean stating that fields of this type are configurable.
   *
   * @var boolean
   */
  public $configurable = TRUE;

  /**
   * A boolean stating that fields of this type cannot be created through the UI.
   *
   * @var boolean
   */
  public $no_ui = FALSE;

  /**
   * {@inheritdoc}
   */
  public $list_class;

}
