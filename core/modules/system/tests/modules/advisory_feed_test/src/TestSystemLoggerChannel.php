<?php

declare(strict_types=1);

namespace Drupal\advisory_feed_test;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LogLevel;

/**
 * Provides a decorator for the 'logger.channel.system' service for testing.
 */
final class TestSystemLoggerChannel extends LoggerChannel {

  /**
   * Constructs an AdvisoriesTestHttpClient object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $innerLogger
   *   The decorated logger.channel.system service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(
    protected LoggerChannelInterface $innerLogger,
    protected StateInterface $state,
  ) {
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Tests\system\Functional\SecurityAdvisories\SecurityAdvisoriesTestTrait::assertServiceAdvisoryLoggedErrors()
   */
  public function log($level, $message, array $context = []): void {
    if ($level === LogLevel::ERROR) {
      $messages = $this->state->get('advisory_feed_test.error_messages', []);
      $messages[] = $message;
      $this->state->set('advisory_feed_test.error_messages', $messages);
    }
    $this->innerLogger->log($level, $message, $context);
  }

}
