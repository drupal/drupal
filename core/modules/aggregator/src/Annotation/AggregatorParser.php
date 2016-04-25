<?php

namespace Drupal\aggregator\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for aggregator parser plugins.
 *
 * Plugin Namespace: Plugin\aggregator\parser
 *
 * For a working example, see \Drupal\aggregator\Plugin\aggregator\parser\DefaultParser
 *
 * @see \Drupal\aggregator\Plugin\AggregatorPluginManager
 * @see \Drupal\aggregator\Plugin\ParserInterface
 * @see \Drupal\aggregator\Plugin\AggregatorPluginSettingsBase
 * @see plugin_api
 *
 * @Annotation
 */
class AggregatorParser extends Plugin {

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
