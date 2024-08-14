<?php

namespace Drupal\Core\Layout\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Layout\LayoutDefinition;

/**
 * Defines a Layout attribute object.
 *
 * Layouts are used to define a list of regions and then output render arrays
 * in each of the regions, usually using a template.
 *
 * Plugin Namespace: Plugin\Layout
 *
 * @see \Drupal\Core\Layout\LayoutInterface
 * @see \Drupal\Core\Layout\LayoutDefault
 * @see \Drupal\Core\Layout\LayoutPluginManager
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Layout extends Plugin {

  /**
   * Any additional properties and values.
   *
   * @var array
   *
   * @see \Drupal\Core\Layout\LayoutDefinition::$additional
   */
  public readonly array $additional;

  /**
   * Constructs a Layout attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   (optional) The human-readable name. @todo Deprecate optional label in
   *   https://www.drupal.org/project/drupal/issues/3392572.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $category
   *   (optional) The human-readable category. @todo Deprecate optional category
   *   in https://www.drupal.org/project/drupal/issues/3392572.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) The description for advanced layouts.
   * @param string|null $template
   *   (optional) The template file to render the layout.
   * @param string $theme_hook
   *   (optional) The template hook to render the layout.
   * @param string|null $path
   *   (optional) Path (relative to the module or theme) to resources like icon or template.
   * @param string|null $library
   *   (optional) The asset library.
   * @param string|null $icon
   *   (optional) The path to the preview image (relative to the 'path' given).
   * @param string[][]|null $icon_map
   *   (optional) The icon map.
   * @param array $regions
   *   (optional) An associative array of regions in this layout.
   * @param string|null $default_region
   *   (optional) The default region.
   * @param class-string $class
   *   (optional) The layout plugin class.
   * @param \Drupal\Core\Plugin\Context\ContextDefinitionInterface[] $context_definitions
   *   (optional) The context definition.
   * @param array $config_dependencies
   *   (optional) The config dependencies.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   * @param mixed $additional
   *   (optional) Additional properties passed in that can be used by a deriver.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?TranslatableMarkup $category = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?string $template = NULL,
    public readonly string $theme_hook = 'layout',
    public readonly ?string $path = NULL,
    public readonly ?string $library = NULL,
    public readonly ?string $icon = NULL,
    public readonly ?array $icon_map = NULL,
    public readonly array $regions = [],
    public readonly ?string $default_region = NULL,
    public string $class = LayoutDefault::class,
    public readonly array $context_definitions = [],
    public readonly array $config_dependencies = [],
    public readonly ?string $deriver = NULL,
    ...$additional,
  ) {
    // Layout definitions support arbitrary properties being passed in, which
    // are stored in the 'additional' property in LayoutDefinition. The variadic
    // 'additional' parameter here saves arbitrary parameters passed into the
    // 'additional' property in this attribute class. The 'additional' property
    // gets passed to the LayoutDefinition constructor in ::get().
    // @see \Drupal\Core\Layout\LayoutDefinition::$additional
    // @see \Drupal\Core\Layout\LayoutDefinition::get()
    $this->additional = $additional;
  }

  /**
   * {@inheritdoc}
   */
  public function get(): LayoutDefinition {
    return new LayoutDefinition(parent::get());
  }

}
