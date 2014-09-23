<?php

/**
 * @file
 * Contains \Drupal\Core\Annotation\QueueWorker.
 */

namespace Drupal\Core\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Declare queue workers that need to be run periodically.
 *
 * While there can be only one hook_cron() process running at the same time,
 * there can be any number of processes defined here running. Because of
 * this, long running tasks are much better suited for this API. Items queued
 * in hook_cron() might be processed in the same cron run if there are not many
 * items in the queue, otherwise it might take several requests, which can be
 * run in parallel.
 *
 * You can create queues, add items to them, claim them, etc. without using a
 * QueueWorker plugin if you want, however, you need to take care of processing
 * the items in the queue in that case. See \Drupal\Core\Cron for an example.
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
   * An associative array containing the optional key:
   *   - time: (optional) How much time Drupal cron should spend on calling
   *     this worker in seconds. Defaults to 15.
   *
   * @var array (optional)
   */
  public $cron;

}
