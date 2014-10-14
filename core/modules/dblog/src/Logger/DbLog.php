<?php

/**
 * @file
 * Contains \Drupal\dblog\Logger\DbLog.
 */

namespace Drupal\dblog\Logger;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Logs events in the watchdog database table.
 */
class DbLog implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * Constructs a DbLog object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection object.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   */
  public function __construct(Connection $database, LogMessageParserInterface $parser) {
    $this->database = $database;
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array()) {
    // Remove any backtraces since they may contain an unserializable variable.
    unset($context['backtrace']);

    // Convert PSR3-style messages to String::format() style, so they can be
    // translated too in runtime.
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);

    $this->database
      ->insert('watchdog')
      ->fields(array(
        'uid' => $context['uid'],
        'type' => substr($context['channel'], 0, 64),
        'message' => $message,
        'variables' => serialize($message_placeholders),
        'severity' => $level,
        'link' => substr($context['link'], 0, 255),
        'location' => $context['request_uri'],
        'referer' => $context['referer'],
        'hostname' => substr($context['ip'], 0, 128),
        'timestamp' => $context['timestamp'],
      ))
      ->execute();
  }

}
