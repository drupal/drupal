<?php

declare(strict_types=1);

namespace Drupal\views\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a ViewsRow attribute for plugin discovery.
 *
 * @see \Drupal\views\Plugin\views\style\StylePluginBase
 *
 * @ingroup views_row_plugins
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ViewsRow extends Plugin {

  /**
   * Constructs an ViewsRow attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $title
   *   (optional) The plugin title used in the views UI.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $short_title
   *   (optional) The short title used in the views UI.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $help
   *   (optional) A short help string; this is displayed in the views UI.
   * @param string[] $display_types
   *   (optional) The types of the display this plugin can be used with.
   *   For example the Feed display defines the type 'feed', so only rss style
   *   and row plugins can be used in the views UI.
   * @param string[] $base
   *   (optional) The base tables on which this style plugin can be used.
   *   If no base table is specified the plugin can be used with all tables.
   * @param string|null $theme
   *   (optional) The theme function used to render the style output.
   * @param bool $no_ui
   *   (optional) Whether the plugin should be not selectable in the UI.
   *   If set to TRUE, you can still use it via the API in config files.
   *   Defaults to FALSE.
   * @param bool $register_theme
   *   (optional) Whether to register a theme function automatically. Defaults
   *   to TRUE.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $title = NULL,
    public readonly ?TranslatableMarkup $short_title = NULL,
    public readonly ?TranslatableMarkup $help = NULL,
    public readonly array $display_types = [],
    public readonly array $base = [],
    public readonly ?string $theme = NULL,
    public readonly bool $no_ui = FALSE,
    public readonly bool $register_theme = TRUE,
    public readonly ?string $deriver = NULL,
  ) {}

}
