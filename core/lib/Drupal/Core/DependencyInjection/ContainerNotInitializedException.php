<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\ContainerNotInitializedException.
 */

namespace Drupal\Core\DependencyInjection;

/**
 * Exception thrown when a method is called that requires a container, but the
 * container is not initialized yet.
 *
 * @see \Drupal
 */
class ContainerNotInitializedException extends \RuntimeException {

}
