<?php
namespace Drupal\views\Plugin\views\aggregate;

use Drupal\views\Plugin\views\PluginBase;

/**
 * Aggregate plugin for SQL stddev_samp function.
 *
 * @ViewsAggregate(
 *   id = "stddev_samp",
 *   function = "stddev_samp",
 *   title = @Translation("Standard deviation (sample)"),
 *   method = "aggregationMethodSimple",
 *   handler = {
 *     "argument" = "groupby_numeric",
 *     "field" = "numeric",
 *     "filter" = "groupby_numeric",
 *     "sort" = "groupby_numeric"
 *   },
 *   help = @Translation("Population Standard Deviation."),
 * )
 */

class Stddev_Samp extends AggregatePluginBase {


  // Class methods…
}