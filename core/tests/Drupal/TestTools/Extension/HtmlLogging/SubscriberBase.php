<?php

declare(strict_types=1);

namespace Drupal\TestTools\Extension\HtmlLogging;

/**
 * Base class for PHPUnit event subscribers related to HTML logging.
 *
 * @internal
 */
abstract class SubscriberBase {

  public function __construct(
    private readonly HtmlOutputLogger $logger,
  ) {
  }

  protected function logger(): HtmlOutputLogger {
    return $this->logger;
  }

}
