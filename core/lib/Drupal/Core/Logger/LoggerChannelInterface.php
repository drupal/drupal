<?php

/**
 * @file
 * Contains \Drupal\Core\Logger\LoggerChannelInterface.
 */

namespace Drupal\Core\Logger;

use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Logger channel interface.
 */
interface LoggerChannelInterface extends LoggerInterface {

  /**
   * Sets the request stack.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack|null $requestStack
   *   The current request object.
   */
  public function setRequestStack(RequestStack $requestStack = NULL);

  /**
   * Sets the current user.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $current_user
   *   The current user object.
   */
  public function setCurrentUser(AccountInterface $current_user = NULL);

  /**
   * Sets the loggers for this channel.
   *
   * @param array $loggers
   *   An array of arrays of \Psr\Log\LoggerInterface keyed by priority.
   */
  public function setLoggers(array $loggers);

  /**
   * Adds a logger.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The PSR-3 logger to add.
   * @param int $priority
   *   The priority of the logger being added.
   */
  public function addLogger(LoggerInterface $logger, $priority = 0);

}
