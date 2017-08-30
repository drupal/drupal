<?php

namespace Drupal\simpletest;

use Drupal\Tests\node\Traits\NodeCreationTrait as BaseNodeCreationTrait;

/**
 * Provides methods to create node based on default settings.
 *
 * This trait is meant to be used only by test classes.
 *
 * @deprecated in Drupal 8.4.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\NodeCreationTrait instead.
 *
 * @see https://www.drupal.org/node/2884454
 */
trait NodeCreationTrait {

  use BaseNodeCreationTrait;

}
