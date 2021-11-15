<?php

namespace Drupal\Component\EventDispatcher;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event as ContractsEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as ContractsEventDispatcherInterface;

/**
 * A performance optimized container aware event dispatcher.
 *
 * This version of the event dispatcher contains the following optimizations
 * in comparison to the Symfony event dispatcher component:
 *
 * <dl>
 *   <dt>Faster instantiation of the event dispatcher service</dt>
 *   <dd>
 *     Instead of calling <code>addSubscriberService</code> once for each
 *     subscriber, a precompiled array of listener definitions is passed
 *     directly to the constructor. This is faster by roughly an order of
 *     magnitude. The listeners are collected and prepared using a compiler
 *     pass.
 *   </dd>
 *   <dt>Lazy instantiation of listeners</dt>
 *   <dd>
 *     Services are only retrieved from the container just before invocation.
 *     Especially when dispatching the KernelEvents::REQUEST event, this leads
 *     to a more timely invocation of the first listener. Overall dispatch
 *     runtime is not affected by this change though.
 *   </dd>
 * </dl>
 */
class ContainerAwareEventDispatcher implements EventDispatcherInterface {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Listener definitions.
   *
   * A nested array of listener definitions keyed by event name and priority.
   * A listener definition is an associative array with one of the following key
   * value pairs:
   * - callable: A callable listener
   * - service: An array of the form [service id, method]
   *
   * A service entry will be resolved to a callable only just before its
   * invocation.
   *
   * @var array
   */
  protected $listeners;

  /**
   * Whether listeners need to be sorted prior to dispatch, keyed by event name.
   *
   * @var TRUE[]
   */
  protected $unsorted;

  /**
   * Constructs a container aware event dispatcher.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param array $listeners
   *   A nested array of listener definitions keyed by event name and priority.
   *   The array is expected to be ordered by priority. A listener definition is
   *   an associative array with one of the following key value pairs:
   *   - callable: A callable listener
   *   - service: An array of the form [service id, method]
   *   A service entry will be resolved to a callable only just before its
   *   invocation.
   */
  public function __construct(ContainerInterface $container, array $listeners = []) {
    $this->container = $container;
    $this->listeners = $listeners;
    $this->unsorted = [];
  }

  /**
   * {@inheritdoc}
   */
  public function dispatch($event/*, string $event_name = NULL*/) {
    $event_name = 1 < \func_num_args() ? func_get_arg(1) : NULL;
    if (\is_object($event)) {
      $class_name = get_class($event);
      $event_name = $event_name ?? $class_name;

      $deprecation_message = 'Symfony\Component\EventDispatcher\Event is deprecated in drupal:9.1.0 and will be replaced by Symfony\Contracts\EventDispatcher\Event in drupal:10.0.0. A new Drupal\Component\EventDispatcher\Event class is available to bridge the two versions of the class. See https://www.drupal.org/node/3159012';

      // Trigger a deprecation error if the deprecated Event class is used
      // directly.
      if ($class_name === 'Symfony\Component\EventDispatcher\Event') {
        @trigger_error($deprecation_message, E_USER_DEPRECATED);
      }
      // Also try to trigger deprecation errors when classes are in the Drupal
      // namespace and inherit directly from the deprecated class. If a class is
      // in the Symfony namespace or a different one, we have to assume those
      // will be updated by the dependency itself. Exclude the Drupal Event
      // bridge class as a special case, otherwise it's pointless.
      elseif ($class_name !== 'Drupal\Component\EventDispatcher\Event' && strpos($class_name, 'Drupal') !== FALSE) {
        if (get_parent_class($event) === 'Symfony\Component\EventDispatcher\Event') {
          @trigger_error($deprecation_message, E_USER_DEPRECATED);
        }
      }
    }
    elseif (\is_string($event) && (NULL === $event_name || $event_name instanceof ContractsEvent || $event_name instanceof Event)) {
      @trigger_error('Calling the Symfony\Component\EventDispatcher\EventDispatcherInterface::dispatch() method with a string event name as the first argument is deprecated in drupal:9.1.0, an Event object will be required instead in drupal:10.0.0. See https://www.drupal.org/node/3154407', E_USER_DEPRECATED);
      $swap = $event;
      $event = $event_name ?? new Event();
      $event_name = $swap;
    }
    else {
      throw new \TypeError(sprintf('Argument 1 passed to "%s::dispatch()" must be an object, %s given.', ContractsEventDispatcherInterface::class, \gettype($event)));
    }

    if (isset($this->listeners[$event_name])) {
      // Sort listeners if necessary.
      if (isset($this->unsorted[$event_name])) {
        krsort($this->listeners[$event_name]);
        unset($this->unsorted[$event_name]);
      }

      // Invoke listeners and resolve callables if necessary.
      foreach ($this->listeners[$event_name] as $priority => &$definitions) {
        foreach ($definitions as $key => &$definition) {
          if (!isset($definition['callable'])) {
            $definition['callable'] = [$this->container->get($definition['service'][0]), $definition['service'][1]];
          }
          if (is_array($definition['callable']) && isset($definition['callable'][0]) && $definition['callable'][0] instanceof \Closure) {
            $definition['callable'][0] = $definition['callable'][0]();
          }

          call_user_func($definition['callable'], $event, $event_name, $this);
          if ($event->isPropagationStopped()) {
            return $event;
          }
        }
      }
    }

    return $event;
  }

  /**
   * {@inheritdoc}
   */
  public function getListeners($event_name = NULL): array {
    $result = [];

    if ($event_name === NULL) {
      // If event name was omitted, collect all listeners of all events.
      foreach (array_keys($this->listeners) as $event_name) {
        $listeners = $this->getListeners($event_name);
        if (!empty($listeners)) {
          $result[$event_name] = $listeners;
        }
      }
    }
    elseif (isset($this->listeners[$event_name])) {
      // Sort listeners if necessary.
      if (isset($this->unsorted[$event_name])) {
        krsort($this->listeners[$event_name]);
        unset($this->unsorted[$event_name]);
      }

      // Collect listeners and resolve callables if necessary.
      foreach ($this->listeners[$event_name] as $priority => &$definitions) {
        foreach ($definitions as $key => &$definition) {
          if (!isset($definition['callable'])) {
            $definition['callable'] = [$this->container->get($definition['service'][0]), $definition['service'][1]];
          }
          if (is_array($definition['callable']) && isset($definition['callable'][0]) && $definition['callable'][0] instanceof \Closure) {
            $definition['callable'][0] = $definition['callable'][0]();
          }

          $result[] = $definition['callable'];
        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getListenerPriority($event_name, $listener): ?int {
    if (!isset($this->listeners[$event_name])) {
      return NULL;
    }
    if (is_array($listener) && isset($listener[0]) && $listener[0] instanceof \Closure) {
      $listener[0] = $listener[0]();
    }
    // Resolve service definitions if the listener has not been found so far.
    foreach ($this->listeners[$event_name] as $priority => &$definitions) {
      foreach ($definitions as $key => &$definition) {
        if (!isset($definition['callable'])) {
          // Once the callable is retrieved we keep it for subsequent method
          // invocations on this class.
          $definition['callable'] = [
            $this->container->get($definition['service'][0]),
            $definition['service'][1],
          ];
        }
        if (is_array($definition['callable']) && isset($definition['callable'][0]) && $definition['callable'][0] instanceof \Closure) {
          $definition['callable'][0] = $definition['callable'][0]();
        }
        if ($definition['callable'] === $listener) {
          return $priority;
        }
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasListeners($event_name = NULL): bool {
    if ($event_name !== NULL) {
      return !empty($this->listeners[$event_name]);
    }

    foreach ($this->listeners as $event_listeners) {
      if ($event_listeners) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function addListener($event_name, $listener, $priority = 0) {
    $this->listeners[$event_name][$priority][] = ['callable' => $listener];
    $this->unsorted[$event_name] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function removeListener($event_name, $listener) {
    if (!isset($this->listeners[$event_name])) {
      return;
    }

    foreach ($this->listeners[$event_name] as $priority => $definitions) {
      foreach ($definitions as $key => $definition) {
        if (!isset($definition['callable'])) {
          if (!$this->container->initialized($definition['service'][0])) {
            continue;
          }
          $definition['callable'] = [$this->container->get($definition['service'][0]), $definition['service'][1]];
        }

        if (is_array($definition['callable']) && isset($definition['callable'][0]) && $definition['callable'][0] instanceof \Closure && !$listener instanceof \Closure) {
          $definition['callable'][0] = $definition['callable'][0]();
        }

        if (is_array($definition['callable']) && isset($definition['callable'][0]) && !$definition['callable'][0] instanceof \Closure && is_array($listener) && isset($listener[0]) && $listener[0] instanceof \Closure) {
          $listener[0] = $listener[0]();
        }
        if ($definition['callable'] === $listener) {
          unset($definitions[$key]);
        }
      }
      if ($definitions) {
        $this->listeners[$event_name][$priority] = $definitions;
      }
      else {
        unset($this->listeners[$event_name][$priority]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addSubscriber(EventSubscriberInterface $subscriber) {
    foreach ($subscriber->getSubscribedEvents() as $event_name => $params) {
      if (is_string($params)) {
        $this->addListener($event_name, [$subscriber, $params]);
      }
      elseif (is_string($params[0])) {
        $this->addListener($event_name, [$subscriber, $params[0]], $params[1] ?? 0);
      }
      else {
        foreach ($params as $listener) {
          $this->addListener($event_name, [$subscriber, $listener[0]], $listener[1] ?? 0);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeSubscriber(EventSubscriberInterface $subscriber) {
    foreach ($subscriber->getSubscribedEvents() as $event_name => $params) {
      if (is_array($params) && is_array($params[0])) {
        foreach ($params as $listener) {
          $this->removeListener($event_name, [$subscriber, $listener[0]]);
        }
      }
      else {
        $this->removeListener($event_name, [$subscriber, is_string($params) ? $params : $params[0]]);
      }
    }
  }

}
