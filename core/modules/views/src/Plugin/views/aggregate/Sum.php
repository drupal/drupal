<?php
namespace Drupal\views\Plugin\views\aggregate;

use Drupal\views\Plugin\views\PluginBase;

/**
 * Aggregate plugin for SQL SUM function.
 *
 * @ViewsAggregate(
 *   id = "sum",
 *   function = "sum",
 *   title = @Translation("Sum"),
 *   method = "aggregationMethodSimple",
 *   handler = {
 *     "argument" = "groupby_numeric",
 *     "field" = "numeric",
 *     "filter" = "groupby_numeric",
 *     "sort" = "groupby_numeric"
 *   },
 *   help = @Translation("Sum."),
 * )
 */
class Sum extends AggregatePluginBase {


  // Class methods…
}