<?php

namespace Drupal\simpletest;

@trigger_error(__NAMESPACE__ . '\BlockCreationTrait is deprecated in Drupal 8.4.x. Will be removed before Drupal 9.0.0. Use \Drupal\Tests\block\Traits\BlockCreationTrait instead. See https://www.drupal.org/node/2884454.', E_USER_DEPRECATED);

use Drupal\Tests\block\Traits\BlockCreationTrait as BaseBlockCreationTrait;

/**
 * Provides methods to create and place block with default settings.
 *
 * This trait is meant to be used only by test classes.
 *
 * @deprecated in drupal:8.4.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Tests\block\Traits\BlockCreationTrait instead.
 *
 * @see https://www.drupal.org/node/2884454
 */
trait BlockCreationTrait {

  use BaseBlockCreationTrait;

}
