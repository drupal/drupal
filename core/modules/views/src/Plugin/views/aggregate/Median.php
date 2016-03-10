<?php
namespace Drupal\views\Plugin\views\aggregate;

use Drupal\views\Plugin\views\PluginBase;

/**
 * Aggregate plugin for SQL Median function.
 *
 * @ViewsAggregate(
 *   id = "median",
 *   function = "median",
 *   title = @Translation("Median"),
 *   method = "aggregationMethodSimple",
 *   handler = {
 *     "argument" = "views_handler_argument_group_by_numeric",
 *     "filter" = "views_handler_filter_group_by_numeric",
 *     "sort" = "views_handler_sort_group_by_numeric"
 *   },
 *   help = @Translation("Median."),
 * )
 */

class Median extends AggregatePluginBase {


  // Class methods…
}