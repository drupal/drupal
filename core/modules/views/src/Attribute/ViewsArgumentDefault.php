<?php

declare(strict_types=1);

namespace Drupal\views\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a ViewsArgument attribute for plugin discovery.
 *
 * @see \Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase
 *
 * @ingroup views_argument_default_plugins
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ViewsArgumentDefault extends Plugin {

  /**
   * Constructs a ViewsArgument attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $title
   *   The plugin title used in the views UI.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $short_title
   *   (optional) The short title used in the views UI.
   * @param bool $no_ui
   *   (optional) Whether the plugin should be not selectable in the UI. If it's
   *   set to TRUE, you can still use it via the API in config files.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $title = NULL,
    public readonly ?TranslatableMarkup $short_title = NULL,
    public readonly bool $no_ui = FALSE,
    public readonly ?string $deriver = NULL,
  ) {}

}
