<?php

namespace Drupal\Core\Logger;

/**
 * A copy of \Psr\Log\LoggerTrait that uses RFC 5424 compliant log levels.
 *
 * Internal Drupal logger implementations should use this trait instead of
 * \Psr\Log\LoggerTrait. Callers of those implementations are responsible for
 * translating any other log level format to RFC 5424 compliant integers.
 *
 * @see https://groups.google.com/forum/#!topic/php-fig/Rc5YDhNdGz4
 * @see https://www.drupal.org/node/2267545
 */
trait RfcLoggerTrait {

  /**
   * {@inheritdoc}
   */
  public function emergency(string|\Stringable $message, array $context = []): void {
    $this->log(RfcLogLevel::EMERGENCY, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function alert(string|\Stringable $message, array $context = []): void {
    $this->log(RfcLogLevel::ALERT, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function critical(string|\Stringable $message, array $context = []): void {
    $this->log(RfcLogLevel::CRITICAL, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function error(string|\Stringable $message, array $context = []): void {
    $this->log(RfcLogLevel::ERROR, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function warning(string|\Stringable $message, array $context = []): void {
    $this->log(RfcLogLevel::WARNING, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function notice(string|\Stringable $message, array $context = []): void {
    $this->log(RfcLogLevel::NOTICE, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function info(string|\Stringable $message, array $context = []): void {
    $this->log(RfcLogLevel::INFO, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function debug(string|\Stringable $message, array $context = []): void {
    $this->log(RfcLogLevel::DEBUG, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  abstract public function log($level, string|\Stringable $message, array $context = []): void;

}
