<?php

/**
 * @file
 * Definition of Drupal\Core\Queue\ReliableQueueInterface.
 */

namespace Drupal\Core\Queue;

/**
 * Reliable queue interface.
 *
 * Classes implementing this interface preserve the order of messages and
 * guarantee that every item will be executed at least once.
 *
 * @ingroup queue
 */
interface ReliableQueueInterface extends QueueInterface {
}
