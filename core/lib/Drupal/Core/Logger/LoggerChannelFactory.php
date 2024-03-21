<?php

namespace Drupal\Core\Logger;

use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a factory for logging channels.
 */
class LoggerChannelFactory implements LoggerChannelFactoryInterface {

  /**
   * The request stack.
   */
  protected ?RequestStack $requestStack = NULL;

  /**
   * The current user.
   */
  protected ?AccountInterface $currentUser = NULL;

  /**
   * Array of all instantiated logger channels keyed by channel name.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface[]
   */
  protected $channels = [];

  /**
   * An array of arrays of \Psr\Log\LoggerInterface keyed by priority.
   *
   * @var array
   */
  protected $loggers = [];

  /**
   * Constructs a LoggerChannelFactory.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack|null $requestStack
   *   (optional) The request stack.
   * @param \Drupal\Core\Session\AccountInterface|null $currentUser
   *   (optional) The current user.
   */
  public function __construct(
    ?RequestStack $requestStack = NULL,
    ?AccountInterface $currentUser = NULL,
  ) {
    $this->requestStack = $requestStack;
    $this->currentUser = $currentUser;
    if (!$requestStack) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $requestStack argument is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3416354', E_USER_DEPRECATED);
      $this->requestStack = \Drupal::service('request_stack');
    }
    if (!$currentUser) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $currentUser argument is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3416354', E_USER_DEPRECATED);
      $this->currentUser = \Drupal::service('current_user');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($channel) {
    if (!$this->requestStack || !$this->currentUser) {
      @trigger_error('Calling ' . __METHOD__ . ' without calling the constructor is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3416354', E_USER_DEPRECATED);
      $this->requestStack = \Drupal::service('request_stack');
      $this->currentUser = \Drupal::service('current_user');
    }

    if (!isset($this->channels[$channel])) {
      $instance = new LoggerChannel($channel);

      // Set the request_stack and current_user services on the channel.
      // It is up to the channel to determine if there is a current request.
      $instance->setRequestStack($this->requestStack);
      $instance->setCurrentUser($this->currentUser);

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

  /**
   * Sets the service container.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *    dependency injection instead.
   *
   * @see https://www.drupal.org/node/3416354
   */
  public function setContainer() {
    @trigger_error('Calling ' . __METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use dependency injection instead. See https://www.drupal.org/node/3416354', E_USER_DEPRECATED);
  }

  /**
   * {@inheritdoc}
   */
  public function __get(string $name) {
    if ($name === 'container') {
      @trigger_error('Accessing the container property in ' . __CLASS__ . ' is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use dependency injection instead. See https://www.drupal.org/node/3416354', E_USER_DEPRECATED);
      return \Drupal::getContainer();
    }
  }

}
