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
   * @see \Drupal\block\Event\BlockConditionContextEvent
   */
  const CONDITION_CONTEXT = 'block.condition_context';

}
