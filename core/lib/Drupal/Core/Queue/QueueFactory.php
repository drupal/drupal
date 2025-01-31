<?php

namespace Drupal\Core\Queue;

use Drupal\Core\Site\Settings;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

/**
 * Defines the queue factory.
 */
class QueueFactory {

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
   * Constructs QueueFactory object.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The site settings.
   * @param \Psr\Container\ContainerInterface $container
   *   A service locator that contains the queue services.
   */
  public function __construct(
    Settings $settings,
    #[AutowireLocator('queue_factory')]
    protected ContainerInterface $container,
  ) {
    $this->settings = $settings;
  }

  /**
   * Constructs a new queue.
   *
   * @param string $name
   *   The name of the queue to work with.
   * @param bool $reliable
   *   (optional) TRUE if the ordering of items and guaranteeing every item
   *   executes at least once is important, FALSE if scalability is the main
   *   concern. Defaults to FALSE.
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
      $this->queues[$name] = $factory->get($name);
    }
    return $this->queues[$name];
  }

}
