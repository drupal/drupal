<?php

namespace Drupal\block_content;

/**
 * Defines events for the block_content module.
 *
 * @see \Drupal\block_content\Event\BlockContentGetDependencyEvent
 *
 * @internal
 */
final class BlockContentEvents {

  /**
   * Name of the event when getting the dependency of a non-reusable block.
   *
   * This event allows modules to provide a dependency for non-reusable block
   * access if
   * \Drupal\block_content\Access\DependentAccessInterface::getAccessDependency()
   * did not return a dependency during access checking.
   *
   * @Event
   *
   * @see \Drupal\block_content\Event\BlockContentGetDependencyEvent
   * @see \Drupal\block_content\BlockContentAccessControlHandler::checkAccess()
   *
   * @var string
   */
  const BLOCK_CONTENT_GET_DEPENDENCY = 'block_content.get_dependency';

}
