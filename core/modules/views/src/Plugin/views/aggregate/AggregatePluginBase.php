<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\aggregate\AggregatePluginBase.
 */

namespace Drupal\views\Plugin\views\aggregate;

use Drupal\views\Plugin\views\PluginBase;

/**
 * @defgroup views_aggregate_plugins Views query aggregate plugins
 * @{
 * Plugins to handle query aggregates in views.
 *
 * Aggregates allow access to aggregate function provided by SQL and database extensions.
 *
 * Aggregate plugins extend \Drupal\views\Plugin\views\aggregate\AggregatePluginBase. They
 * must be annotated with \Drupal\views\Annotation\ViewsAggregate annotation,
 * and they must be in namespace directory Plugin\views\aggregate.
 *
 * @ingroup views_aggregate_plugins
 * @see plugin_api
 */

/**
 * Base class for views aggregate plugins.
 */
abstract class AggregatePluginBase extends PluginBase {

}

/**
 * @}
 */
