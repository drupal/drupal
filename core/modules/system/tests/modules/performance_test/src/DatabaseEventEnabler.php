<?php

namespace Drupal\performance_test;

use Drupal\Core\Database\Connection;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Database\Event\StatementExecutionEndEvent;
use Drupal\Core\Database\Event\StatementExecutionStartEvent;

class DatabaseEventEnabler implements HttpKernelInterface {

  public function __construct(protected readonly HttpKernelInterface $httpKernel, protected readonly Connection $connection) {}

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE): Response {
    if ($type === static::MAIN_REQUEST) {
      $this->connection->enableEvents([
        // StatementExecutionStartEvent must be enabled in order for
        // StatementExecutionEndEvent to be fired, even though we only subscribe
        // to the latter event.
        StatementExecutionStartEvent::class,
        StatementExecutionEndEvent::class,
      ]);
    }
    return $this->httpKernel->handle($request, $type, $catch);
  }

}
