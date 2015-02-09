<?php

/**
 * @file
 * Contains \Drupal\block\EventSubscriber\BlockContextSubscriberBase.
 */

namespace Drupal\block\EventSubscriber;

use Drupal\block\Event\BlockContextEvent;
use Drupal\block\Event\BlockEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a base class for block context subscribers.
 */
abstract class BlockContextSubscriberBase implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[BlockEvents::ACTIVE_CONTEXT][] = 'onBlockActiveContext';
    $events[BlockEvents::ADMINISTRATIVE_CONTEXT][] = 'onBlockAdministrativeContext';
    return $events;
  }

  /**
   * Determines the available run-time contexts.
   *
   * For blocks to render correctly, all of the contexts that they require
   * must be populated with values. So this method must set a value for each
   * context that it adds. For example:
   * @code
   *   // Determine a specific node to pass as context to blocks.
   *   $node = ...
   *
   *   // Set that specific node as the value of the 'node' context.
   *   $context = new Context(new ContextDefinition('entity:node'));
   *   $context->setContextValue($node);
   *   $event->setContext('node.node', $context);
   * @endcode
   *
   * @param \Drupal\block\Event\BlockContextEvent $event
   *   The Event to which to register available contexts.
   */
  abstract public function onBlockActiveContext(BlockContextEvent $event);

  /**
   * Determines the available configuration-time contexts.
   *
   * When a block is being configured, the configuration UI must know which
   * named contexts are potentially available, but does not care about the
   * value, since the value can be different for each request, and might not
   * be available at all during the configuration UI's request.
   *
   * For example:
   * @code
   *   // During configuration, there is no specific node to pass as context.
   *   // However, inform the system that a context named 'node.node' is
   *   // available, and provide its definition, so that blocks can be
   *   // configured to use it. When the block is rendered, the value of this
   *   // context will be supplied by onBlockActiveContext().
   *   $context = new Context(new ContextDefinition('entity:node'));
   *   $event->setContext('node.node', $context);
   * @endcode
   *
   * @param \Drupal\block\Event\BlockContextEvent $event
   *   The Event to which to register available contexts.
   *
   * @see static::onBlockActiveContext()
   */
  abstract public function onBlockAdministrativeContext(BlockContextEvent $event);

}
