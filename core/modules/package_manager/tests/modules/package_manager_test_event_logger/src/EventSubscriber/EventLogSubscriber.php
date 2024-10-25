<?php

declare(strict_types=1);

namespace Drupal\package_manager_test_event_logger\EventSubscriber;

use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Drupal\package_manager\Event\PostApplyEvent;
use Drupal\package_manager\Event\PostCreateEvent;
use Drupal\package_manager\Event\PostRequireEvent;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\Event\StageEvent;
use Drupal\package_manager\PathLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines an event subscriber to test logging during events in Package Manager.
 */
final class EventLogSubscriber implements EventSubscriberInterface {

  /**
   * The name of the log file to write to.
   *
   * @var string
   */
  public const LOG_FILE_NAME = 'package_manager_test_event.log';

  /**
   * Excludes the log file from Package Manager operations.
   *
   * @param \Drupal\package_manager\Event\CollectPathsToExcludeEvent $event
   *   The event being handled.
   */
  public function excludeLogFile(CollectPathsToExcludeEvent $event): void {
    $event->addPathsRelativeToProjectRoot([self::LOG_FILE_NAME]);
  }

  /**
   * Logs all events in the stage life cycle.
   *
   * @param \Drupal\package_manager\Event\StageEvent $event
   *   The event object.
   */
  public function logEventInfo(StageEvent $event): void {
    $log_file = \Drupal::service(PathLocator::class)->getProjectRoot() . '/' . self::LOG_FILE_NAME;

    if (file_exists($log_file)) {
      $log_data = file_get_contents($log_file);
      $log_data = json_decode($log_data, TRUE, flags: JSON_THROW_ON_ERROR);
    }
    else {
      $log_data = [];
    }

    $log_data[] = [
      'event' => $event::class,
      'stage' => $event->stage::class,
    ];
    file_put_contents($log_file, json_encode($log_data, JSON_UNESCAPED_SLASHES));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // This subscriber should run before every other validator, because the
    // purpose of this subscriber is to log all dispatched events.
    // @see \Drupal\package_manager\Validator\BaseRequirementsFulfilledValidator
    // @see \Drupal\package_manager\Validator\BaseRequirementValidatorTrait
    // @see \Drupal\package_manager\Validator\EnvironmentSupportValidator
    return [
      CollectPathsToExcludeEvent::class => ['excludeLogFile'],
      PreCreateEvent::class => ['logEventInfo', PHP_INT_MAX],
      PostCreateEvent::class => ['logEventInfo', PHP_INT_MAX],
      PreRequireEvent::class => ['logEventInfo', PHP_INT_MAX],
      PostRequireEvent::class => ['logEventInfo', PHP_INT_MAX],
      PreApplyEvent::class => ['logEventInfo', PHP_INT_MAX],
      PostApplyEvent::class => ['logEventInfo', PHP_INT_MAX],
    ];
  }

}
