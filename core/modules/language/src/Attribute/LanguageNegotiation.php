<?php

namespace Drupal\language\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a language negotiation attribute object.
 *
 * Plugin Namespace: Plugin\LanguageNegotiation
 *
 * For a working example, see
 * \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationBrowser.
 *
 * @see \Drupal\language\LanguageNegotiator
 * @see \Drupal\language\LanguageNegotiationMethodManager
 * @see \Drupal\language\LanguageNegotiationMethodInterface
 * @see hook_language_negotiation_info_alter()
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class LanguageNegotiation extends Plugin {

  /**
   * Constructs an LanguageNegotiation attribute.
   *
   * @param string $id
   *   The language negotiation plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $name
   *   The human-readable name of the language negotiation plugin.
   * @param string[]|null $types
   *   An array of language types, such as the
   *    \Drupal\Core\Language\LanguageInterface::TYPE_* constants.
   *   If a language negotiation plugin does not specify which language types it
   *   should be used with, it will be available for all the configurable
   *   language types.
   * @param int $weight
   *   The default weight of the language negotiation plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   The description of the language negotiation plugin.
   * @param string|null $config_route_name
   *   (optional) The route pointing to the plugin's configuration page.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $name,
    public readonly ?array $types = NULL,
    public readonly int $weight = 0,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?string $config_route_name = NULL,
  ) {}

}
