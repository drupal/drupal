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
   * The Queue Worker Manager service.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueWorkerManager;

  /**
   * Constructs a queue factory.
   */
  public function __construct(Settings $settings, QueueWorkerManagerInterface $queue_worker_manager) {
    $this->settings = $settings;
    $this->queueWorkerManager = $queue_worker_manager;
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
      $service_name = $this->getServiceName($name, $reliable);
      /*
       * todo: remove $service_name detection legacy logic before
       *   drupal:10.0.0 release.
       */
      if ($service_name === NULL) {
        // If it is a reliable queue, check the specific settings first.
        if ($reliable) {
          $service_name = $this->settings::get('queue_reliable_service_' . $name);
          if ($service_name) {
            @trigger_error("The \"queue_reliable_service_{$name}\" key is deprecated in drupal:9.2.0 and has no effect in drupal:10.0.0. Use hook_queue_info_alter() and `\$queue['{$name}']['queue_reliable_service'] = '{$service_name}';` alter instead.", \E_USER_DEPRECATED);
          }
        }
        // If no reliable queue was defined, check the service and global
        // settings, fall back to queue.database.
        if (empty($service_name)) {
          $service_name = $this->settings::get('queue_service_' . $name);
          if ($service_name) {
            @trigger_error("The \"queue_service_{$name}\" key is deprecated in drupal:9.2.0 and has no effect in drupal:10.0.0. Use hook_queue_info_alter() and `\$queue['{$name}']['queue_service'] = '{$service_name}';` alter instead.", \E_USER_DEPRECATED);
          }
          /*
           * todo: move default fallback to getServiceName() method before
           *   drupal:10.0.0 release.
           */
          else {
            $service_name = $this->settings::get('queue_default', 'queue.database');
          }
        }
      }
      $this->queues[$name] = $this->container->get($service_name)->get($name);
    }
    return $this->queues[$name];
  }

  /**
   * Get the service name which responsible to handle the queue.
   *
   * @param string $queue_id
   *   Queue Worker ID. The ID of the queue.
   * @param bool $reliable
   *   (optional) TRUE if the ordering of items and guaranteeing every item executes at
   *   least once is important, FALSE if scalability is the main concern. Defaults
   *   to FALSE.
   *
   * @return string|null
   *   Service name. The 'queue.database' is used by the default.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getServiceName(string $queue_id, bool $reliable = FALSE): ?string {
    $definition = $this->queueWorkerManager->getDefinition($queue_id, FALSE);
    $service_name = NULL;
    if ($reliable && isset($definition['queue_reliable_service'])) {
      $service_name = $definition['queue_reliable_service'];
    }
    elseif (isset($definition['queue_service'])) {
      $service_name = $definition['queue_service'];
    }
    return $service_name;
  }

}
