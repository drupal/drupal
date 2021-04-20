<?php

namespace Drupal\Core\Layout\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Layout\LayoutDefinition;

/**
 * Defines a Layout annotation object.
 *
 * Layouts are used to define a list of regions and then output render arrays
 * in each of the regions, usually using a template.
 *
 * Plugin namespace: Plugin\Layout
 *
 * @see \Drupal\Core\Layout\LayoutInterface
 * @see \Drupal\Core\Layout\LayoutDefault
 * @see \Drupal\Core\Layout\LayoutPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Layout extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name.
   *
   * @var string
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A description for advanced layouts.
   *
   * This property is optional and it does not need to be declared.
   *
   * Sometimes layouts are so complex that the name is insufficient to describe
   * a layout such that a visually impaired administrator could layout a page
   * for a non-visually impaired audience. If specified, it will provide a
   * description that is used for accessibility purposes.
   *
   * @var string
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The human-readable category.
   *
   * @var string
   *
   * @see \Drupal\Component\Plugin\CategorizingPluginManagerInterface
   *
   * @ingroup plugin_translatable
   */
  public $category;

  /**
   * The template file to render this layout (relative to the 'path' given).
   *
   * This property is optional and it does not need to be declared.
   *
   * If specified, then the layout_discovery module will register the template
   * with hook_theme() and the module or theme registering this layout does not
   * need to do it.
   *
   * @var string
   *
   * @see hook_theme()
   */
  public $template;

  /**
   * The theme hook used to render this layout.
   *
   * This property is optional and it does not need to be declared.
   *
   * If specified, it's assumed that the module or theme registering this layout
   * will also register the theme hook with hook_theme() itself. This is
   * mutually exclusive with 'template' - you can't specify both.
   *
   * @var string
   *
   * @see hook_theme()
   */
  public $theme_hook = 'layout';

  /**
   * Path (relative to the module or theme) to resources like icon or template.
   *
   * This property is optional and it does not need to be declared.
   *
   * @var string
   */
  public $path;

  /**
   * The asset library.
   *
   * This property is optional and it does not need to be declared.
   *
   * @var string
   */
  public $library;

  /**
   * The path to the preview image (relative to the 'path' given).
   *
   * This property is optional and it does not need to be declared.
   *
   * @var string
   */
  public $icon;

  /**
   * The icon map.
   *
   * This property is optional and it does not need to be declared.
   *
   * @var string[][]
   *
   * @see \Drupal\Core\Layout\Icon\IconBuilderInterface::build()
   */
  public $icon_map;

  /**
   * An associative array of regions in this layout.
   *
   * The key of the array is the machine name of the region, and the value is
   * an associative array with the following keys:
   * - label: (string) The human-readable name of the region.
   *
   * Any remaining keys may have special meaning for the given layout plugin,
   * but are undefined here.
   *
   * @var array
   */
  public $regions = [];

  /**
   * The default region.
   *
   * @var string
   */
  public $default_region;

  /**
   * The layout plugin class.
   *
   * This default value is used for plugins defined in layouts.yml that do not
   * specify a class themselves.
   *
   * @var string
   */
  public $class = LayoutDefault::class;

  /**
   * {@inheritdoc}
   */
  public function get() {
    return new LayoutDefinition($this->definition);
  }

}
