<?php

namespace Drupal\simpletest;

use Drupal\Tests\node\Traits\ContentTypeCreationTrait as BaseContentTypeCreationTrait;

/**
 * Provides methods to create content type from given values.
 *
 * This trait is meant to be used only by test classes.
 *
 * @deprecated in Drupal 8.4.x. Will be removed before Drupal 9.0.0. Use
 *   \Drupal\Tests\node\Traits\ContentTypeCreationTrait instead.
 *
 * @see https://www.drupal.org/node/2884454
 */
trait ContentTypeCreationTrait {

  use BaseContentTypeCreationTrait;

}
