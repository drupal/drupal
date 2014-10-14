<?php

/**
 * @file
 * Contains \Drupal\Core\Logger\RfcLoggerTrait.
 */

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
   * Implements \Psr\Log\LoggerInterface::emergency()
   */
  public function emergency($message, array $context = array()) {
    $this->log(RfcLogLevel::EMERGENCY, $message, $context);
  }

  /**
   * Implements \Psr\Log\LoggerInterface::alert()
   */
  public function alert($message, array $context = array()) {
    $this->log(RfcLogLevel::ALERT, $message, $context);
  }

  /**
   * Implements \Psr\Log\LoggerInterface::critical()
   */
  public function critical($message, array $context = array()) {
    $this->log(RfcLogLevel::CRITICAL, $message, $context);
  }

  /**
   * Implements \Psr\Log\LoggerInterface::error()
   */
  public function error($message, array $context = array()) {
    $this->log(RfcLogLevel::ERROR, $message, $context);
  }

  /**
   * Implements \Psr\Log\LoggerInterface::warning()
   */
  public function warning($message, array $context = array()) {
    $this->log(RfcLogLevel::WARNING, $message, $context);
  }

  /**
   * Implements \Psr\Log\LoggerInterface::notice()
   */
  public function notice($message, array $context = array()) {
    $this->log(RfcLogLevel::NOTICE, $message, $context);
  }

  /**
   * Implements \Psr\Log\LoggerInterface::info()
   */
  public function info($message, array $context = array()) {
    $this->log(RfcLogLevel::INFO, $message, $context);
  }

  /**
   * Implements \Psr\Log\LoggerInterface::debug()
   */
  public function debug($message, array $context = array()) {
    $this->log(RfcLogLevel::DEBUG, $message, $context);
  }

  /**
   * Implements \Psr\Log\LoggerInterface::log()
   */
  abstract public function log($level, $message, array $context = array());

}
