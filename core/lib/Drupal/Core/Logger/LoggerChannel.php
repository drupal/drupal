<?php

/**
 * @file
 * Contains \Drupal\Core\Logger\LoggerChannel.
 */

namespace Drupal\Core\Logger;

use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a logger channel that most implementations will use.
 */
class LoggerChannel implements LoggerChannelInterface {
  use LoggerTrait;

  /**
   * The name of the channel of this logger instance.
   *
   * @var string
   */
  protected $channel;

  /**
   * Map of PSR Log constants to Watchdog log constants.
   *
   * @todo Move watchdog constants here in https://www.drupal.org/node/2267545.
   *
   * @var array
   */
  protected $levelTranslation = array(
    LogLevel::EMERGENCY => WATCHDOG_EMERGENCY,
    LogLevel::ALERT => WATCHDOG_ALERT,
    LogLevel::CRITICAL => WATCHDOG_CRITICAL,
    LogLevel::ERROR => WATCHDOG_ERROR,
    LogLevel::WARNING => WATCHDOG_WARNING,
    LogLevel::NOTICE => WATCHDOG_NOTICE,
    LogLevel::INFO => WATCHDOG_INFO,
    LogLevel::DEBUG => WATCHDOG_DEBUG,
  );

  /**
   * An array of arrays of \Psr\Log\LoggerInterface keyed by priority.
   *
   * @var array
   */
  protected $loggers = array();

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a LoggerChannel object
   *
   * @param string $channel
   *   The channel name for this instance.
   */
  public function __construct($channel) {
    $this->channel = $channel;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array()) {
    // Merge in defaults.
    $context += array(
      'channel' => $this->channel,
      'link' => '',
      'user' => NULL,
      'uid' => 0,
      'request_uri' => '',
      'referer' => '',
      'ip' => '',
      'timestamp' => time(),
    );
    // Some context values are only available when in a request context.
    if ($this->requestStack && $request = $this->requestStack->getCurrentRequest()) {
      $context['request_uri'] = $request->getUri();
      $context['referer'] = $request->headers->get('Referer', '');
      $context['ip'] = $request->getClientIP();
      if ($this->currentUser) {
        $context['user'] = $this->currentUser;
        $context['uid'] = $this->currentUser->id();
      }
    }

    if (is_string($level)) {
      // Convert to integer equivalent for consistency with RFC 3164.
      $level = $this->levelTranslation[$level];
    }
    // Call all available loggers.
    foreach ($this->sortLoggers() as $logger) {
      $logger->log($level, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestStack(RequestStack $requestStack = NULL) {
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrentUser(AccountInterface $current_user = NULL) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function setLoggers(array $loggers) {
    $this->loggers = $loggers;
  }

  /**
   * {@inheritdoc}
   */
  public function addLogger(LoggerInterface $logger, $priority = 0) {
    $this->loggers[$priority][] = $logger;
  }

  /**
   * Sorts loggers according to priority.
   *
   * @return array
   *   An array of sorted loggers by priority.
   */
  protected function sortLoggers() {
    $sorted = array();
    krsort($this->loggers);

    foreach ($this->loggers as $loggers) {
      $sorted = array_merge($sorted, $loggers);
    }
    return $sorted;
  }

}
