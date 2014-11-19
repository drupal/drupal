<?php

/**
 * @file
 * Contains \Drupal\block\Event\BlockContextEvent.
 */

namespace Drupal\block\Event;

use Drupal\Core\Plugin\Context\ContextInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event subscribers can add context to be used by the block and its conditions.
 *
 * @see \Drupal\block\Event\BlockEvents::ACTIVE_CONTEXT
 * @see \Drupal\block\Event\BlockEvents::ADMINISTRATIVE_CONTEXT
 */
class BlockContextEvent extends Event {

  /**
   * The array of available contexts for blocks.
   *
   * @var array
   */
  protected $contexts = [];

  /**
   * Sets the context object for a given name.
   *
   * @param string $name
   *   The name to store the context object under.
   * @param \Drupal\Core\Plugin\Context\ContextInterface $context
   *   The context object to set.
   *
   * @return $this
   */
  public function setContext($name, ContextInterface $context) {
    $this->contexts[$name] = $context;
    return $this;
  }

  /**
   * Returns the context objects.
   *
   * @return \Drupal\Component\Plugin\Context\ContextInterface[]
   *   An array of contexts that have been provided.
   */
  public function getContexts() {
    return $this->contexts;
  }

}
