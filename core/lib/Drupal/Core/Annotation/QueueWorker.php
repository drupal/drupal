<?php

namespace Drupal\Core\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Declare a worker class for processing a queue item.
 *
 * Worker plugins are used by some queues for processing the individual items
 * in the queue. In that case, the ID of the worker plugin needs to match the
 * machine name of a queue, so that you can retrieve the queue back end by
 * calling \Drupal\Core\Queue\QueueFactory::get($plugin_id).
 *
 * \Drupal\Core\Cron::processQueues() processes queues that use workers; they
 * can also be processed outside of the cron process.
 *
 * Some queues do not use worker plugins: you can create queues, add items to
 * them, claim them, etc. without using a QueueWorker plugin. However, you will
 * need to take care of processing the items in the queue in that case. You can
 * look at \Drupal\Core\Cron::processQueues() for an example of how to process
 * a queue that uses workers, and adapt it to your queue.
 *
 * Plugin Namespace: Plugin\QueueWorker
 *
 * For a working example, see
 * \Drupal\aggregator\Plugin\QueueWorker\AggregatorRefresh.
 *
 * @see \Drupal\Core\Queue\QueueWorkerInterface
 * @see \Drupal\Core\Queue\QueueWorkerBase
 * @see \Drupal\Core\Queue\QueueWorkerManager
 * @see plugin_api
 *
 * @Annotation
 */
class QueueWorker extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable title of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /**
   * An associative array containing an optional key.
   *
   * This property is optional and it does not need to be declared.
   *
   * Available keys:
   * - time (optional): How much time Drupal cron should spend on calling this
   *   worker in seconds. Defaults to 15.
   *
   * @var array
   */
  public $cron;

}
