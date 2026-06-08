<?php

declare(strict_types=1);

namespace Drupal\Core\Command;

use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Logs to the console. A logger is set during DrupalApplication->bootstrap().
 */
class DrupalConsoleLogger extends AbstractLogger implements LoggerAwareInterface {
  use LoggerAwareTrait;

  public function __construct(
    protected LogMessageParserInterface $parser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []): void {
    // Populate the message placeholders and then replace them in the message.
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);
    $message = empty($message_placeholders) ? $message : strtr($message, $message_placeholders);
    $this->logger?->log(self::toPsr3($level), $message, []);
  }

  /**
   * Returns the PSR-3 log level for a given RFC 5424 severity level.
   *
   * @param int $level
   *   The RFC 5424 severity level.
   *
   * @return string
   *   A PSR-3 log level.
   *
   * @throws \Psr\Log\InvalidArgumentException
   *   If the severity level is not recognized.
   */
  public static function toPsr3(int $level): string {
    return match ($level) {
      RfcLogLevel::EMERGENCY => LogLevel::EMERGENCY,
      RfcLogLevel::ALERT => LogLevel::ALERT,
      RfcLogLevel::CRITICAL => LogLevel::CRITICAL,
      RfcLogLevel::ERROR => LogLevel::ERROR,
      RfcLogLevel::WARNING => LogLevel::WARNING,
      RfcLogLevel::NOTICE => LogLevel::NOTICE,
      RfcLogLevel::INFO => LogLevel::INFO,
      RfcLogLevel::DEBUG => LogLevel::DEBUG,
      default => throw new \InvalidArgumentException("Invalid log level: $level"),
    };
  }

  /**
   * A custom map where notices are printed by default.
   */
  public static function verbosityLevelMap(): array {
    return [
      LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
      LogLevel::ALERT => OutputInterface::VERBOSITY_NORMAL,
      LogLevel::CRITICAL => OutputInterface::VERBOSITY_NORMAL,
      LogLevel::ERROR => OutputInterface::VERBOSITY_NORMAL,
      LogLevel::WARNING => OutputInterface::VERBOSITY_NORMAL,
      LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
      LogLevel::INFO => OutputInterface::VERBOSITY_VERY_VERBOSE,
      LogLevel::DEBUG => OutputInterface::VERBOSITY_DEBUG,
    ];
  }

}
