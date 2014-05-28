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
