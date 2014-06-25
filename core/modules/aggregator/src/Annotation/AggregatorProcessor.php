<?php

/**
 * @file
 * Contains \Drupal\aggregator\Annotation\AggregatorProcessor.
 */

namespace Drupal\aggregator\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for aggregator processor plugins.
 *
 * Plugin Namespace: Plugin\aggregator\processor
 *
 * For a working example, see \Drupal\aggregator\Plugin\aggregator\processor\DefaultProcessor
 *
 * @see \Drupal\aggregator\Plugin\AggregatorPluginManager
 * @see \Drupal\aggregator\Plugin\ProcessorInterface
 * @see \Drupal\aggregator\Plugin\AggregatorPluginSettingsBase
 * @see plugin_api
 *
 * @Annotation
 */
class AggregatorProcessor extends Plugin {

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
