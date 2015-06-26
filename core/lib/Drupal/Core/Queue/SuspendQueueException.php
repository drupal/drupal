<?php

/**
 * @file
 * Contains \Drupal\Core\Queue\SuspendQueueException.
 */

namespace Drupal\Core\Queue;

/**
 * Exception class to throw to indicate that a cron queue should be skipped.
 *
 * An implementation of \Drupal\Core\Queue\QueueWorkerInterface::processItem()
 * throws this class of exception to indicate that processing of the whole queue
 * should be skipped. This should be thrown rather than a normal Exception if
 * the problem encountered by the queue worker is such that it can be deduced
 * that workers of subsequent items would encounter it too. For example, if a
 * remote site that the queue worker depends on appears to be inaccessible.
 */
class SuspendQueueException extends \RuntimeException {}
