<?php

namespace Drupal\Core\Test;

use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Records the number of times specific events occur.
 *
 * @see \Drupal\Core\Test\PerformanceTestRecorder::registerService()
 */
class PerformanceTestRecorder implements EventSubscriberInterface {

  /**
   * The state service for persistent storage if necessary.
   */
  protected $state;

  /**
   * @var array
   */
  protected static $record = [];

  /**
   * PerformanceTestRecorder constructor.
   *
   * @param bool $persistent
   *   Whether to save the record to state.
   * @param \Drupal\Core\State\StateInterface|null $state
   *   (optional) The state service for persistent storage. Required if
   *   $persistent is TRUE.
   */
  public function __construct(bool $persistent, ?StateInterface $state) {
    if ($persistent && !$state) {
      throw new \InvalidArgumentException('If $persistent is TRUE then $state must be set');
    }
    $this->state = $state;
  }

  public function getCount(string $type, string $name): int {
    $count = 0;
    if ($this->state) {
      $record = $this->state->get('drupal.performance_test_recorder', []);
      $count += $record[$type][$name] ?? 0;
    }
    $count += self::$record[$type][$name] ?? 0;
    return $count;
  }

  /**
   * Records the occurrence of an event.
   *
   * @param string $type
   *   The type of event to record.
   * @param string $name
   *   The name of the event to record.
   */
  public function record(string $type, string $name): void {
    if ($this->state) {
      $record = $this->state->get('drupal.performance_test_recorder', []);
      isset($record[$type][$name]) ? $record[$type][$name]++ : $record[$type][$name] = 1;
      $this->state->set('drupal.performance_test_recorder', $record);
    }
    else {
      isset(self::$record[$type][$name]) ? self::$record[$type][$name]++ : self::$record[$type][$name] = 1;
    }
  }

  /**
   * Records a router rebuild.
   */
  public function onRouteBuilderFinish() {
    $this->record('event', RoutingEvents::FINISHED);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[RoutingEvents::FINISHED][] = ['onRouteBuilderFinish', -9999999];
    return $events;
  }

  /**
   * Registers core.performance.test.recorder service.
   *
   * @param string $services_file
   *   Path to the services file to register the service in.
   * @param bool $persistent
   *   Whether the recorder should be in persistent mode. The persistent mode
   *   records using the state service so that the recorder will work on the
   *   site under test when requests are made. However, if we want to measure
   *   something used by the state system then this will be recursive. Also in
   *   kernel tests using state is unnecessary.
   */
  public static function registerService(string $services_file, bool $persistent): void {

    $services = Yaml::parse(file_get_contents($services_file));
    if (isset($services['services']['core.performance.test.recorder'])) {
      // Once the service has been marked as persistent don't change that.
      $persistent = $persistent || $services['services']['core.performance.test.recorder']['arguments'][0];
    }
    $services['services']['core.performance.test.recorder'] = [
      'class' => PerformanceTestRecorder::class,
      'arguments' => [$persistent, $persistent ? '@state' : NULL],
      'tags' => [['name' => 'event_subscriber']],
    ];
    file_put_contents($services_file, Yaml::dump($services));
  }

}
