<?php

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
   * Maximum call depth to self::log() for a single log message.
   *
   * It's very easy for logging channel code to call out to other library code
   * that will create log messages. In that case, we will recurse back in to
   * LoggerChannel::log() multiple times while processing a single originating
   * message. To prevent infinite recursion, we track the call depth and bail
   * out at LoggerChannel::MAX_CALL_DEPTH iterations.
   *
   * @var int
   */
  const MAX_CALL_DEPTH = 5;

  /**
   * Number of times LoggerChannel::log() has been called for a single message.
   *
   * @var int
   */
  protected $callDepth = 0;

  /**
   * The name of the channel of this logger instance.
   *
   * @var string
   */
  protected $channel;

  /**
   * Map of PSR3 log constants to RFC 5424 log constants.
   *
   * @var array
   */
  protected $levelTranslation = array(
    LogLevel::EMERGENCY => RfcLogLevel::EMERGENCY,
    LogLevel::ALERT => RfcLogLevel::ALERT,
    LogLevel::CRITICAL => RfcLogLevel::CRITICAL,
    LogLevel::ERROR => RfcLogLevel::ERROR,
    LogLevel::WARNING => RfcLogLevel::WARNING,
    LogLevel::NOTICE => RfcLogLevel::NOTICE,
    LogLevel::INFO => RfcLogLevel::INFO,
    LogLevel::DEBUG => RfcLogLevel::DEBUG,
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
    if ($this->callDepth == self::MAX_CALL_DEPTH) {
      return;
    }
    $this->callDepth++;

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
      try {
        if ($this->currentUser) {
          $context['user'] = $this->currentUser;
          $context['uid'] = $this->currentUser->id();
        }
      }
      catch (\Exception $e) {
        // An exception might be thrown if the database connection is not
        // available or due to another unexpected reason. It is more important
        // to log the error that we already have so any additional exceptions
        // are ignored.
      }
    }

    if (is_string($level)) {
      // Convert to integer equivalent for consistency with RFC 5424.
      $level = $this->levelTranslation[$level];
    }
    // Call all available loggers.
    foreach ($this->sortLoggers() as $logger) {
      $logger->log($level, $message, $context);
    }

    $this->callDepth--;
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
