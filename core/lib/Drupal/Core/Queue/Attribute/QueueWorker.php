<?php

namespace Drupal\Core\Queue\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

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
 * \Drupal\locale\Plugin\QueueWorker\LocaleTranslation.
 *
 * @see \Drupal\Core\Queue\QueueWorkerInterface
 * @see \Drupal\Core\Queue\QueueWorkerBase
 * @see \Drupal\Core\Queue\QueueWorkerManager
 * @see plugin_api
 *
 * @ingroup queue
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class QueueWorker extends Plugin {

  /**
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $title
   *   The human-readable title of the plugin.
   * @param array|null $cron
   *   (optional) An associative array of settings for cron. The array has one
   *   key, time, which is set to the time Drupal cron should spend on calling
   *   this worker in seconds. The default is set in
   *   \Drupal\Core\Queue\QueueWorkerManager::processDefinition().
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $title = NULL,
    public readonly ?array $cron = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
