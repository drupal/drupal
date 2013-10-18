<?php

/**
 * @file
 * Contains \Drupal\field\Annotation\FieldWidget.
 */

namespace Drupal\Core\Field\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FieldWidget annotation object.
 *
 * Widgets handle how fields are displayed in edit forms.
 *
 * Additional annotation keys for widgets can be defined in
 * hook_field_widget_info_alter().
 *
 * @Annotation
 *
 * @see \Drupal\Core\Field\WidgetPluginManager
 * @see \Drupal\Core\Field\WidgetInterface
 */
class FieldWidget extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the widget type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A short description of the widget type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * The name of the widget class.
   *
   * This is not provided manually, it will be added by the discovery mechanism.
   *
   * @var string
   */
  public $class;

  /**
   * An array of field types the widget supports.
   *
   * @var array
   */
  public $field_types = array();

  /**
   * An array whose keys are the names of the settings available to the widget
   * type, and whose values are the default values for those settings.
   *
   * @var array
   */
  public $settings = array();

  /**
   * Does the field widget handles multiple values at once.
   *
   * @var bool
   */
  public $multiple_values = FALSE;

  /**
   * An integer to determine the weight of this widget relative to other widgets
   * in the Field UI when selecting a widget for a given field instance.
   *
   * @var int optional
   */
  public $weight = NULL;

}
