<?php

namespace Drupal\syslog\Logger;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

// cspell:ignore ndelay

/**
 * Redirects logging messages to syslog.
 */
class SysLog implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * A configuration object containing syslog settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * Stores whether there is a system logger connection opened or not.
   *
   * @var bool
   */
  protected $connectionOpened = FALSE;

  /**
   * Constructs a SysLog object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory object.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LogMessageParserInterface $parser) {
    $this->config = $config_factory->get('syslog.settings');
    $this->parser = $parser;
  }

  /**
   * Opens a connection to the system logger.
   */
  protected function openConnection() {
    if (!$this->connectionOpened) {
      // Do not connect if identity or facility are not configured.
      $identity = $this->config->get('identity');
      $facility = $this->config->get('facility');
      if ($identity === NULL || $facility === NULL) {
        return;
      }
      $this->connectionOpened = openlog($identity, LOG_NDELAY, $facility);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, string|\Stringable $message, array $context = []): void {
    global $base_url;

    $format = $this->config->get('format');
    // If no format is configured then a message will not be written to syslog
    // so return early. This occurs during installation of the syslog module
    // before configuration has been written.
    if (empty($format)) {
      return;
    }

    // Ensure we have a connection available.
    $this->openConnection();
    if (!$this->connectionOpened) {
      return;
    }

    // Populate the message placeholders and then replace them in the message.
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);
    $message = empty($message_placeholders) ? $message : strtr($message, $message_placeholders);

    $entry = strtr($format, [
      '!base_url' => $base_url,
      '!timestamp' => $context['timestamp'],
      '!type' => $context['channel'],
      '!ip' => $context['ip'],
      '!request_uri' => $context['request_uri'],
      '!referer' => $context['referer'],
      '!severity' => $level,
      '!uid' => $context['uid'],
      '!link' => strip_tags($context['link']),
      '!message' => strip_tags($message),
    ]);

    $this->syslogWrapper($level, $entry);
  }

  /**
   * A syslog wrapper to make syslog functionality testable.
   *
   * @param int $level
   *   The syslog priority.
   * @param string $entry
   *   The message to send to syslog function.
   */
  protected function syslogWrapper($level, $entry) {
    syslog($level, $entry);
  }

}
