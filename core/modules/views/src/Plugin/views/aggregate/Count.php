<?php
namespace Drupal\views\Plugin\views\aggregate;

use Drupal\views\Plugin\views\PluginBase;

/**
 * Aggregate plugin for SQL COUNT function.
 *
 * @ViewsAggregate(
 *   id = "count",
 *   function = "count",
 *   title = @Translation("Count"),
 *   method = "aggregationMethodSimple",
 *   handler = {
 *     "argument" = "groupby_numeric",
 *     "field" = "numeric",
 *     "filter" = "groupby_numeric",
 *     "sort" = "groupby_numeric"
 *   },
 *   help = @Translation("Count."),
 * )
 */

class Count extends AggregatePluginBase {


  // Class methods…
}