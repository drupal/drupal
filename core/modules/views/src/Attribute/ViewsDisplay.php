<?php

declare(strict_types=1);

namespace Drupal\views\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Plugin attribute object for views display plugins.
 *
 * @see \Drupal\views\Plugin\views\display\DisplayPluginBase
 *
 * @ingroup views_display_plugins
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ViewsDisplay extends Plugin {

  /**
   * Constructs a views display attribute object.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The plugin title used in the views UI.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $short_title
   *   (optional) The short title used in the views UI.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $admin
   *   (optional) The administrative name of the display.
   *   The name is displayed on the Views overview and also used as default name
   *   for new displays.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $help
   *   (optional) A short help string; this is displayed in the views UI.
   * @param bool $uses_menu_links
   *   (optional) Whether or not to use hook_menu() to register a route.
   *   Defaults to FALSE.
   * @param bool $uses_route
   *   (optional) Does the display plugin registers routes to the route.
   *   Defaults to FALSE.
   * @param bool $uses_hook_block
   *   (optional) Does the display plugin provide blocks. Defaults to FALSE.
   * @param bool $returns_response
   *   (optional) Whether the display returns a response object.
   *   Defaults to FALSE.
   * @param string[]|null $contextual_links_locations
   *   (optional) A list of places where contextual links should be added.
   *   If you don't specify it there will be contextual links rendered for all
   *   displays of a view. If this is not set or regions have been specified,
   *   views will display an option to 'hide contextual links'. Use an empty
   *   array to disable.
   * @param string[] $base
   *   (optional) The base tables on which this exposed form plugin can be used.
   *   If no base table is specified the plugin can be used with all tables.
   * @param string|null $theme
   *   (optional) The theme function used to render the style output.
   * @param bool $no_ui
   *   (optional) Whether the plugin should be not selectable in the UI.
   *   If it's set to TRUE, you can still use it via the API in config files.
   *   Defaults to FALSE.
   * @param bool $register_theme
   *   (optional) Whether to register a theme function automatically. Defaults
   *   to TRUE.
   * @param bool $entity_reference_display
   *   (optional) Custom property, used with \Drupal\views\Views::getApplicableViews().
   *   Defaults to FALSE.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $title,
    public readonly ?TranslatableMarkup $short_title = NULL,
    public readonly ?TranslatableMarkup $admin = NULL,
    public readonly ?TranslatableMarkup $help = NULL,
    public readonly bool $uses_menu_links = FALSE,
    public readonly bool $uses_route = FALSE,
    public readonly bool $uses_hook_block = FALSE,
    public readonly bool $returns_response = FALSE,
    public readonly ?array $contextual_links_locations = NULL,
    public readonly array $base = [],
    public readonly ?string $theme = NULL,
    public readonly bool $no_ui = FALSE,
    public readonly bool $register_theme = TRUE,
    public readonly bool $entity_reference_display = FALSE,
    public readonly ?string $deriver = NULL,
  ) {}

}
