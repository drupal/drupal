<?php

namespace Drupal\Core\Logger;

use Psr\Log\LoggerInterface;

/**
 * Logger channel factory interface.
 */
interface LoggerChannelFactoryInterface {

  /**
   * Retrieves the registered logger for the requested channel.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   *   The registered logger for this channel.
   */
  public function get($channel);

  /**
   * Adds a logger to all the channels.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The PSR-3 logger to add.
   * @param int $priority
   *   The priority of the logger being added.
   *
   * @see \Symfony\Component\HttpKernel\DependencyInjection\LoggerPass
   */
  public function addLogger(LoggerInterface $logger, $priority = 0);

}
