<?php

declare(strict_types=1);

namespace Drupal\Core\Utility;

/**
 * Enumeration for Fiber resume hints.
 *
 * This can be passed to \Fiber::suspend() to allow the loop that processes the
 * fiber to immediately retry or wait. The loop should only use this if there
 * are no other fibers to process.
 *
 * In a number of places, Drupal has adopted a pattern that uses fibers not
 * to wait for external async operations, but group multiple slow operations,
 * such as an entity load or path alias lookup together.
 *
 * This is currently only used by
 * \Drupal\Core\Render\Renderer::executeInRenderContext() and the default is
 * delayed.
 *
 * This may be deprecated and removed again in the future, once the Revolt event
 * loop is adopted in www.drupal.org/project/drupal/issues/3394423.
 *
 * @see \Drupal\Core\Entity\EntityStorageBase::loadMultiple()
 * @see \Drupal\path_alias\AliasManager::getAliasByPath()
 */
enum FiberResumeType {
  case Immediate;
  case Delayed;
}
