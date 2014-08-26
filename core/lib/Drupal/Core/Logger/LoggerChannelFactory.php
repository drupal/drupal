<?php

/**
 * @file
 * Contains \Drupal\Core\Logger\LoggerChannelFactory.
 */

namespace Drupal\Core\Logger;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Defines a factory for logging channels.
 */
class LoggerChannelFactory implements LoggerChannelFactoryInterface, ContainerAwareInterface {
  use ContainerAwareTrait;

  /**
   * Array of all instantiated logger channels keyed by channel name.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface[]
   */
  protected $channels = array();

  /**
   * An array of arrays of \Psr\Log\LoggerInterface keyed by priority.
   *
   * @var array
   */
  protected $loggers = array();

  /**
   * {@inheritdoc}
   */
  public function get($channel) {
    if (!isset($this->channels[$channel])) {
      $instance = new LoggerChannel($channel);

      // If we have a container set the request_stack and current_user services
      // on the channel. It is up to the channel to determine if there is a
      // current request.
      if ($this->container) {
        $instance->setRequestStack($this->container->get('request_stack'));
        $instance->setCurrentUser($this->container->get('current_user'));
      }

      // Pass the loggers to the channel.
      $instance->setLoggers($this->loggers);
      $this->channels[$channel] = $instance;
    }

    return $this->channels[$channel];
  }

  /**
   * {@inheritdoc}
   */
  public function addLogger(LoggerInterface $logger, $priority = 0) {
    // Store it so we can pass it to potential new logger instances.
    $this->loggers[$priority][] = $logger;
    // Add the logger to already instantiated channels.
    foreach ($this->channels as $channel) {
      $channel->addLogger($logger, $priority);
    }
  }

}
