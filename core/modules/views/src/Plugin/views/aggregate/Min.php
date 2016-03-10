<?php
namespace Drupal\views\Plugin\views\aggregate;

use Drupal\views\Plugin\views\PluginBase;

/**
 * Aggregate plugin for SQL MIN function.
 *
 * @ViewsAggregate(
 *   id = "min",
 *   function = "min",
 *   title = @Translation("Minimum"),
 *   method = "aggregationMethodSimple",
 *   handler = {
 *     "argument" = "groupby_numeric",
 *     "field" = "numeric",
 *     "filter" = "groupby_numeric",
 *     "sort" = "groupby_numeric"
 *   },
 *   help = @Translation("Minimum."),
 * )
 */
class Min extends AggregatePluginBase {


  // Class methods…
}