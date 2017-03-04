<?php

namespace Drupal\syslog\Logger;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

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
      $facility = $this->config->get('facility');
      if ($facility === '') {
        $facility = defined('LOG_LOCAL0') ? LOG_LOCAL0 : LOG_USER;
      }
      $this->connectionOpened = openlog($this->config->get('identity'), LOG_NDELAY, $facility);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    global $base_url;

    // Ensure we have a connection available.
    $this->openConnection();

    // Populate the message placeholders and then replace them in the message.
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);
    $message = empty($message_placeholders) ? $message : strtr($message, $message_placeholders);

    $entry = strtr($this->config->get('format'), [
      '!base_url' => $base_url,
      '!timestamp' => $context['timestamp'],
      '!type' => $context['channel'],
      '!ip' => $context['ip'],
      '!request_uri' => $context['request_uri'],
      '!referer' => $context['referer'],
      '!uid' => $context['uid'],
      '!link' => strip_tags($context['link']),
      '!message' => strip_tags($message),
    ]);

    syslog($level, $entry);
  }

}
