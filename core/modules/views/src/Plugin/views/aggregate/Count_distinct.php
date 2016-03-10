<?php
namespace Drupal\views\Plugin\views\aggregate;

use Drupal\views\Plugin\views\PluginBase;

/**
 * Aggregate plugin for SQL COUNT DISTINCT function.
 *
 * @ViewsAggregate(
 *   id = "count_distinct",
 *   function = "count distinct",
 *   title = @Translation("Count DISTINCT"),
 *   method = "aggregationMethodDistinct",
 *   handler = {
 *     "argument" = "groupby_numeric",
 *     "field" = "numeric",
 *     "filter" = "groupby_numeric",
 *     "sort" = "groupby_numeric"
 *   },
 *   help = @Translation("Count DISTINCT."),
 * )
 */
class Count_Distinct extends AggregatePluginBase {


  // Class methods…
}