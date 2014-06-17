<?php

/**
 * @file
 * Contains \Drupal\block\EventSubscriber\BlockContextSubscriberBase.
 */

namespace Drupal\block\EventSubscriber;

use Drupal\block\Event\BlockConditionContextEvent;
use Drupal\block\Event\BlockEvents;
use Drupal\Component\Plugin\Context\ContextInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a base class for block context subscribers.
 */
abstract class BlockConditionContextSubscriberBase implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Condition\ConditionPluginBag
   */
  protected $conditions;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[BlockEvents::CONDITION_CONTEXT][] = 'onBlockConditionContext';
    return $events;
  }

  /**
   * Subscribes to the event and delegates to the subclass.
   */
  public function onBlockConditionContext(BlockConditionContextEvent $event) {
    $this->conditions = $event->getConditions();
    $this->determineBlockContext();
  }

  /**
   * Determines the contexts for a given block.
   */
  abstract protected function determineBlockContext();

  /**
   * Sets the condition context for a given name.
   *
   * @param string $name
   *   The name of the context.
   * @param \Drupal\Component\Plugin\Context\ContextInterface $context
   *   The context to add.
   *
   * @return $this
   */
  public function addContext($name, ContextInterface $context) {
    $this->conditions->addContext($name, $context);
    return $this;
  }

}
