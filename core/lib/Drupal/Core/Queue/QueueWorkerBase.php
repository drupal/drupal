<?php

namespace Drupal\Core\Queue;

use Drupal\Component\Plugin\PluginBase;

/**
 * Provides a base implementation for a QueueWorker plugin.
 *
 * @see \Drupal\Core\Queue\QueueWorkerInterface
 * @see \Drupal\Core\Queue\QueueWorkerManager
 * @see \Drupal\Core\Annotation\QueueWorker
 * @see plugin_api
 */
abstract class QueueWorkerBase extends PluginBase implements QueueWorkerInterface {

}
