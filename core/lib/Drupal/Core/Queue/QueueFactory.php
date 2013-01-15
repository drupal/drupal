<?php

/**
 * @file
 * Contains \Drupal\Core\Queue\QueueFactory.
 */

namespace Drupal\Core\Queue;

use Drupal\Component\Utility\Settings;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Defines the queue factory.
 */
class QueueFactory extends ContainerAware {

  /**
   * Instantiated queues, keyed by name.
   *
   * @var array
   */
  protected $queues = array();

  /**
   * The settings object.
   *
   * @var \Drupal\Component\Utility\Settings
   */
  protected $settings;


  /**
   * Constructs a queue factory.
   */
  function __construct(Settings $settings) {
    $this->settings = $settings;
  }

  /**
   * Constructs a new queue.
   *
   * @param string $name
   *   The name of the queue to work with.
   * @param bool $reliable
   *   (optional) TRUE if the ordering of items and guaranteeing every item executes at
   *   least once is important, FALSE if scalability is the main concern. Defaults
   *   to FALSE.
   *
   * @return \Drupal\Core\QueueStore\QueueInterface
   *   A queue implementation for the given name.
   */
  public function get($name, $reliable = FALSE) {
    if (!isset($this->queues[$name])) {
      // If it is a reliable queue, check the specific settings first.
      if ($reliable) {
        $service_name = $this->settings->get('queue_reliable_service_' . $name);
      }
      // If no reliable queue was defined, check the service and global
      // settings, fall back to queue.database.
      if (empty($service_name)) {
        $service_name = $this->settings->get('queue_service_' . $name, $this->settings->get('queue_default', 'queue.database'));
      }
      $this->queues[$name] = $this->container->get($service_name)->get($name);
    }
    return $this->queues[$name];
  }
}

