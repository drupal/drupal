<?php

declare(strict_types=1);

namespace Drupal\filter\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a filter attribute for plugin discovery.
 *
 * Plugin Namespace: Plugin\Filter
 *
 * For a working example, see \Drupal\filter\Plugin\Filter\FilterHtml
 *
 * @see \Drupal\filter\FilterPluginManager
 * @see \Drupal\filter\Plugin\FilterInterface
 * @see \Drupal\filter\Plugin\FilterBase
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Filter extends Plugin {

  /**
   * Constructs a Filter attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The human-readable name of the filter. This is used as an administrative
   *   summary of what the filter does.
   * @param int $type
   *   The filter type. Values are defined in
   *   \Drupal\filter\Plugin\FilterInterface.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) Additional administrative information about the filter's
   *   behavior.
   * @param int $weight
   *   (optional) A default weight for the filter in new text formats.
   * @param bool $status
   *   (optional) Whether this filter is enabled or disabled by default.
   * @param array $settings
   *   (optional) The default settings for the filter.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $title,
    public readonly int $type,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly int $weight = 0,
    public readonly bool $status = FALSE,
    public readonly array $settings = [],
  ) {}

}
