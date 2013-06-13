<?php

/**
 * @file
 * Contains \Drupal\field\Annotation\FieldFormatter.
 */

namespace Drupal\field\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FieldFormatter annotation object.
 *
 * Formatters handle the display of field values. Formatter hooks are typically
 * called by the Field Attach API field_attach_prepare_view() and
 * field_attach_view() functions.
 *
 * Additional annotation keys for formatters can be defined in
 * hook_field_formatter_info_alter().
 *
 * @Annotation
 *
 * @see \Drupal\field\Plugin\Type\Formatter\FormatterPluginManager
 * @see \Drupal\field\Plugin\Type\Formatter\FormatterInterface
 */
class FieldFormatter extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the formatter type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A short description of the formatter type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * The name of the module providing the formatter.
   *
   * @var string
   */
  public $module;

  /**
   * The name of the field formatter class.
   *
   * This is not provided manually, it will be added by the discovery mechanism.
   *
   * @var string
   */
  public $class;

  /**
   * An array of field types the formatter supports.
   *
   * @var array
   */
  public $field_types = array();

  /**
   * An array whose keys are the names of the settings available to the
   * formatter type, and whose values are the default values for those settings.
   *
   * @var array
   */
  public $settings = array();

}
