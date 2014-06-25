<?php

/**
 * @file
 * Contains \Drupal\aggregator\Annotation\AggregatorFetcher.
 */

namespace Drupal\aggregator\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for aggregator fetcher plugins.
 *
 * Plugin Namespace: Plugin\aggregator\fetcher
 *
 * For a working example, see \Drupal\aggregator\Plugin\aggregator\fetcher\DefaultFetcher
 *
 * @see \Drupal\aggregator\Plugin\AggregatorPluginManager
 * @see \Drupal\aggregator\Plugin\FetcherInterface
 * @see \Drupal\aggregator\Plugin\AggregatorPluginSettingsBase
 * @see plugin_api
 *
 * @Annotation
 */
class AggregatorFetcher extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
