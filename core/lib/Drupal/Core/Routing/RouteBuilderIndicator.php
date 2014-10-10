<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\RouteBuilderIndicator.
 */

namespace Drupal\Core\Routing;

use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * A simple service to be used instead of the route builder.
 *
 * The route builder service is required by quite a few other services,
 * however it has a lot of dependencies. Use this service to ensure that the
 * router is rebuilt.
 */
class RouteBuilderIndicator implements RouteBuilderIndicatorInterface {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * @param \Drupal\Core\State\StateInterface $state
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function setRebuildNeeded() {
    $this->state->set(static::REBUILD_NEEDED, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function isRebuildNeeded() {
    return $this->state->get(static::REBUILD_NEEDED, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function setRebuildDone() {
    $this->state->set(static::REBUILD_NEEDED, FALSE);
  }

}
