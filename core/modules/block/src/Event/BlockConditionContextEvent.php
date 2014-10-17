<?php

/**
 * @file
 * Contains \Drupal\block\Event\BlockContextEvent.
 */

namespace Drupal\block\Event;

use Drupal\Core\Condition\ConditionPluginCollection;
use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps block conditions in order for event subscribers to add context.
 *
 * @see \Drupal\block\Event\BlockEvents::CONDITION_CONTEXT
 */
class BlockConditionContextEvent extends Event {

  /**
   * @var \Drupal\Core\Condition\ConditionPluginCollection
   */
  protected $conditions;

  /**
   * @param \Drupal\Core\Condition\ConditionPluginCollection $conditions
   */
  public function __construct(ConditionPluginCollection $conditions) {
    $this->conditions = $conditions;
  }

  /**
   * @return \Drupal\Core\Block\BlockPluginInterface
   */
  public function getConditions() {
    return $this->conditions;
  }

}
