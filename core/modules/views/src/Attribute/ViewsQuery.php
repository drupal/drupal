<?php

declare(strict_types=1);

namespace Drupal\views\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a ViewsQuery attribute for plugin discovery.
 *
 * @see \Drupal\views\Plugin\views\query\QueryPluginBase
 *
 * @ingroup views_query_plugins
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ViewsQuery extends Plugin {

  /**
   * Constructs an ViewsDisplayExtender attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The plugin title used in the views UI.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $short_title
   *   (optional) The short title used in the views UI.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $help
   *   (optional) A short help string; this is displayed in the views UI.
   * @param bool $no_ui
   *   (optional) Whether the plugin should be not selectable in the UI.
   *   If set to TRUE, you can still use it via the API in config files.
   *   Defaults to FALSE.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $title,
    public readonly ?TranslatableMarkup $short_title = NULL,
    public readonly ?TranslatableMarkup $help = NULL,
    public readonly bool $no_ui = FALSE,
    public readonly ?string $deriver = NULL,
  ) {}

}
