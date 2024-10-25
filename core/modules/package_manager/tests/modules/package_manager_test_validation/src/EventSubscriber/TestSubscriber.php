<?php

declare(strict_types=1);

namespace Drupal\package_manager_test_validation\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Drupal\package_manager\Event\PostApplyEvent;
use Drupal\package_manager\Event\PostCreateEvent;
use Drupal\package_manager\Event\PostRequireEvent;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\Event\StageEvent;
use Drupal\package_manager\Event\StatusCheckEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines an event subscriber for testing validation of Package Manager events.
 */
class TestSubscriber implements EventSubscriberInterface {

  /**
   * The key to use store the test results.
   *
   * @var string
   */
  protected const STATE_KEY = 'package_manager_test_validation';

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Creates a TestSubscriber object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Sets whether a specific event will call exit().
   *
   * This is useful for simulating an unrecoverable (fatal) error when handling
   * the given event.
   *
   * @param string $event
   *   The event class.
   */
  public static function setExit(string $event): void {
    \Drupal::state()->set(self::getStateKey($event), 'exit');
  }

  /**
   * Sets validation results for a specific event.
   *
   * This method is static to enable setting the expected results before this
   * module is enabled.
   *
   * @param \Drupal\package_manager\ValidationResult[]|null $results
   *   The validation results, or NULL to delete stored results.
   * @param string $event
   *   The event class.
   */
  public static function setTestResult(?array $results, string $event): void {
    $key = static::getStateKey($event);

    $state = \Drupal::state();
    if (isset($results)) {
      $state->set($key, $results);
    }
    else {
      $state->delete($key);
    }
  }

  /**
   * Sets an exception to throw for a specific event.
   *
   * This method is static to enable setting the expected results before this
   * module is enabled.
   *
   * @param \Throwable|null $error
   *   The exception to throw, or NULL to delete a stored exception.
   * @param string $event
   *   The event class.
   */
  public static function setException(?\Throwable $error, string $event): void {
    $key = self::getStateKey($event);

    $state = \Drupal::state();
    if (isset($error)) {
      $state->set($key, $error);
    }
    else {
      $state->delete($key);
    }
  }

  /**
   * Computes the state key to use for a given event class.
   *
   * @param string $event
   *   The event class.
   *
   * @return string
   *   The state key under which to store the results for the given event.
   */
  protected static function getStateKey(string $event): string {
    $key = hash('sha256', static::class . $event);
    return static::STATE_KEY . substr($key, 0, 8);
  }

  /**
   * Adds validation results to a stage event.
   *
   * @param \Drupal\package_manager\Event\StageEvent $event
   *   The event object.
   */
  public function handleEvent(StageEvent $event): void {
    $results = $this->state->get(self::getStateKey(get_class($event)), []);

    // Record that value of maintenance mode for each event.
    $this->state->set(get_class($event) . '.' . 'system.maintenance_mode', $this->state->get('system.maintenance_mode'));

    if ($results instanceof \Throwable) {
      throw $results;
    }
    elseif ($results === 'exit') {
      exit();
    }
    elseif (is_string($results)) {
      \Drupal::messenger()->addStatus($results);
      return;
    }
    /** @var \Drupal\package_manager\ValidationResult $result */
    foreach ($results as $result) {
      $event->addResult($result);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PreCreateEvent::class => ['handleEvent', 5],
      PostCreateEvent::class => ['handleEvent', 5],
      PreRequireEvent::class => ['handleEvent', 5],
      PostRequireEvent::class => ['handleEvent', 5],
      PreApplyEvent::class => ['handleEvent', 5],
      PostApplyEvent::class => ['handleEvent', 5],
      StatusCheckEvent::class => ['handleEvent', 5],
    ];
  }

  /**
   * Sets a status message that will be sent to the messenger for an event.
   *
   * @param string $message
   *   Message text.
   * @param string $event
   *   The event class.
   */
  public static function setMessage(string $message, string $event): void {
    $key = static::getStateKey($event);
    $state = \Drupal::state();
    $state->set($key, $message);
  }

}
