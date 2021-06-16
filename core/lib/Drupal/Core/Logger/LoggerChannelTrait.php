<?php

namespace Drupal\Core\Logger;

/**
 * Wrapper methods for the logger factory service.
 *
 * This utility trait should only be used in application-level code, such as
 * classes that would implement ContainerInjectionInterface. Services registered
 * in the Container should not use this trait but inject the appropriate service
 * directly for easier testing.
 *
 * @see \Drupal\Core\DependencyInjection\ContainerInjectionInterface
 */
trait LoggerChannelTrait {

  /**
   * The logger channel factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Gets the logger for a specific channel.
   *
   * @param string $channel
   *   The name of the channel. Can be any string, but the general practice is
   *   to use the name of the subsystem calling this.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger for the given channel.
   *
   * @todo Require the use of injected services:
   *   https://www.drupal.org/node/2733703
   */
  protected function getLogger($channel) {
    if (!$this->loggerFactory) {
      $this->loggerFactory = \Drupal::service('logger.factory');
    }
    return $this->loggerFactory->get($channel);
  }

  /**
   * Injects the logger channel factory.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory service.
   *
   * @return $this
   */
  public function setLoggerFactory(LoggerChannelFactoryInterface $logger_factory) {
    $this->loggerFactory = $logger_factory;

    return $this;
  }

}
