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
   * @see \Drupal\Core\Block\BlockBase::getConditionContexts()
   * @see \Drupal\block\Event\BlockContextEvent
   */
  const ACTIVE_CONTEXT = 'block.active_context';

  /**
   * Name of the event when gathering contexts for plugin configuration.
   *
   * @see \Drupal\block\BlockForm::form()
   * @see \Drupal\block\Event\BlockContextEvent
   */
  const ADMINISTRATIVE_CONTEXT = 'block.administrative_context';

}
