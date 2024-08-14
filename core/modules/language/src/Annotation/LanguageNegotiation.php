<?php

namespace Drupal\language\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a language negotiation annotation object.
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
 *
 * @Annotation
 */
class LanguageNegotiation extends Plugin {

  /**
   * The language negotiation plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * An array of allowed language types.
   *
   * If a language negotiation plugin does not specify which language types it
   * should be used with, it will be available for all the configurable
   * language types.
   *
   * @var string[]
   *   An array of language types, such as the
   *   \Drupal\Core\Language\LanguageInterface::TYPE_* constants.
   */
  public $types;

  /**
   * The default weight of the language negotiation plugin.
   *
   * @var int
   */
  public $weight;

  /**
   * The human-readable name of the language negotiation plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The description of the language negotiation plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The route pointing to the plugin's configuration page.
   *
   * @var string
   */
  public $config_route_name;

}
