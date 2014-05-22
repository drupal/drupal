<?php

/**
 * @file
 * Contains \Drupal\syslog\Logger\SysLog.
 */

namespace Drupal\syslog\Logger;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LogMessageParserInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Redirects logging messages to syslog.
 */
class SysLog implements LoggerInterface {
  use LoggerTrait;

  /**
   * A configuration object containin syslog settings.
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
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory object.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   */
  public function __construct(ConfigFactory $config_factory, LogMessageParserInterface $parser) {
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
  public function log($level, $message, array $context = array()) {
    global $base_url;

    // Ensure we have a connection available.
    $this->openConnection();

    // Populate the message placeholders and then replace them in the message.
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);
    $message = empty($message_placeholders) ? $message : strtr($message, $message_placeholders);

    $entry = strtr($this->config->get('format'), array(
      '!base_url' => $base_url,
      '!timestamp' => $context['timestamp'],
      '!type' => $context['channel'],
      '!ip' => $context['ip'],
      '!request_uri' => $context['request_uri'],
      '!referer' => $context['referer'],
      '!uid' => $context['uid'],
      '!link' => strip_tags($context['link']),
      '!message' => strip_tags($message),
    ));

    syslog($level, $entry);
  }

}
