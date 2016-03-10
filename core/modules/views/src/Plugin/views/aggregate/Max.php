<?php
namespace Drupal\views\Plugin\views\aggregate;

use Drupal\views\Plugin\views\PluginBase;

/**
 * Aggregate plugin for SQL MAX function.
 *
 * @ViewsAggregate(
 *   id = "max",
 *   function = "max",
 *   title = @Translation("Maximum"),
 *   method = "aggregationMethodSimple",
 *   handler = {
 *     "argument" = "groupby_numeric",
 *     "field" = "numeric",
 *     "filter" = "groupby_numeric",
 *     "sort" = "groupby_numeric"
 *   },
 *   help = @Translation("Maximum."),
 * )
 */
class Max extends AggregatePluginBase {


  // Class methods…
}