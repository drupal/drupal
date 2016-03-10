<?php
namespace Drupal\views\Plugin\views\aggregate;

use Drupal\views\Plugin\views\PluginBase;

/**
 * Aggregate plugin for SQL STDDEV_POPfunction.
 *
 * @ViewsAggregate(
 *   id = "stddev_pop",
 *   function = "stddev_pop",
 *   title = @Translation("Standard deviation (pop)"),
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

class Stddev_Pop extends AggregatePluginBase {


  // Class methods…
}