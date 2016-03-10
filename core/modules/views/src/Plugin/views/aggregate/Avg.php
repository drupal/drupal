<?php
namespace Drupal\views\Plugin\views\aggregate;

use Drupal\views\Plugin\views\PluginBase;

/**
 * Aggregate plugin for SQL AVG function.
 *
 * @ViewsAggregate(
 *   id = "avg",
 *   function = "avg",
 *   title = @Translation("Average"),
 *   method = "aggregationMethodSimple",
 *   handler = {
 *     "argument" = "groupby_numeric",
 *     "field" = "numeric",
 *     "filter" = "groupby_numeric",
 *     "sort" = "groupby_numeric"
 *   },
 *   help = @Translation("Average."),
 * )
 */
class Avg extends AggregatePluginBase {


  // Class methods…
}