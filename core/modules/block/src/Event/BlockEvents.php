<?php

/**
 * @file
 * Contains \Drupal\block\Event\BlockEvents.
 */

namespace Drupal\block\Event;

/**
 * Defines events for the Block module.
 */
final class BlockEvents {

  /**
   * Name of the event when gathering condition context for a block plugin.
   *
   * This event allows you to provide additional context that can be used by
   * a condition plugin in order to determine the visibility of a block. The
   * event listener method receives a \Drupal\block\Event\BlockContextEvent
   * instance. Generally any new context is paired with a new condition plugin
   * that interprets the provided context and allows the block system to
   * determine whether or not the block should be displayed.
   *
   * @Event
   *
   * @see \Drupal\Core\Block\BlockBase::getConditionContexts()
   * @see \Drupal\block\Event\BlockContextEvent
   * @see \Drupal\block\EventSubscriber\NodeRouteContext::onBlockActiveContext()
   * @see \Drupal\Core\Condition\ConditionInterface
   */
  const ACTIVE_CONTEXT = 'block.active_context';

  /**
   * Name of the event when gathering contexts for plugin configuration.
   *
   * This event allows you to provide information about your context to the
   * administration UI without having to provide a value for the context. For
   * example, during configuration there is no specific node to pass as context.
   * However, we still need to inform the system that a context named 'node' is
   * available and provide a definition so that blocks can be configured to use
   * it.
   *
   * The event listener method receives a \Drupal\block\Event\BlockContextEvent
   * instance.
   *
   * @Event
   *
   * @see \Drupal\block\BlockForm::form()
   * @see \Drupal\block\Event\BlockContextEvent
   * @see \Drupal\block\EventSubscriber\NodeRouteContext::onBlockAdministrativeContext()
   */
  const ADMINISTRATIVE_CONTEXT = 'block.administrative_context';

}
