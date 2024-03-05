<?php

namespace Drupal\Core\Queue;

use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Defines the queue factory.
 */
class QueueFactory implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * Instantiated queues, keyed by name.
   *
   * @var array
   */
  protected $queues = [];

  /**
   * The settings object.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Constructs a queue factory.
   */
  public function __construct(Settings $settings) {
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
   * @return \Drupal\Core\Queue\QueueInterface
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
      $factory = $this->container->get($service_name);
      if (!$factory instanceof QueueFactoryInterface) {
        @trigger_error(sprintf('Not implementing %s in %s is deprecated in drupal:10.3.0 and the factory will not be discovered in drupal:11.0.0. Implement the interface in your factory class. See https://www.drupal.org/node/3417034', QueueFactoryInterface::class, $factory::class), E_USER_DEPRECATED);
      }
      $this->queues[$name] = $factory->get($name);
    }
    return $this->queues[$name];
  }

}
