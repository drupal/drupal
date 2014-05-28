<?php

/**
 * @file
 * Contains Drupal\system\EventSubscriber\AutomaticCron.
 */

namespace Drupal\system\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * A subscriber running cron when a request terminates.
 */
class AutomaticCron implements EventSubscriberInterface {

  /**
   * The cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * The cron configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The state key value store.
   *
   * Drupal\Core\State\StateInterface;
   */
  protected $state;

  /**
   * Construct a new automatic cron runner.
   *
   * @param \Drupal\Core\CronInterface $cron
   *   The cron service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(CronInterface $cron, ConfigFactoryInterface $config_factory, StateInterface $state) {
    $this->cron = $cron;
    $this->config = $config_factory->get('system.cron');
    $this->state = $state;
  }

  /**
   * Run the automated cron if enabled.
   *
   * @param Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   The Event to process.
   */
  public function onTerminate(PostResponseEvent $event) {
    // If the site is not fully installed, suppress the automated cron run.
    // Otherwise it could be triggered prematurely by Ajax requests during
    // installation.
    if ($this->state->get('install_task') == 'done') {
      $threshold = $this->config->get('threshold.autorun');
      if ($threshold > 0) {
        $cron_next = $this->state->get('system.cron_last', 0) + $threshold;
        if (REQUEST_TIME > $cron_next) {
          $this->cron->run();
        }
      }
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = array('onTerminate', 100);

    return $events;
  }

}
