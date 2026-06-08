<?php

declare(strict_types=1);

namespace Drupal\Core\Command\EventSubscriber;

use Drupal\Core\Command\DrupalConsoleLogger;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Set a logger so that log messages appear in console output.
 */
class ConsoleSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected DrupalConsoleLogger $logger,
  ) {
  }

  /**
   * Set a logger so that log messages appear in console output.
   *
   * @param \Symfony\Component\Console\Event\ConsoleCommandEvent $event
   *   The event.
   */
  public function onCommand(ConsoleCommandEvent $event): void {
    $errorOutput = $event->getOutput();
    if ($event->getOutput() instanceof ConsoleOutputInterface) {
      $errorOutput = $event->getOutput()->getErrorOutput();
    }
    $this->logger->setLogger(new ConsoleLogger($errorOutput, $this->logger->verbosityLevelMap()));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[ConsoleEvents::COMMAND][] = ['onCommand', 500];
    return $events;
  }

}
