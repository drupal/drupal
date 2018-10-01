<?php

namespace Drupal\config\Tests;

@trigger_error(__NAMESPACE__ . '\SchemaCheckTestTrait is deprecated as of 8.3.x, will be removed in before Drupal 9.0.0. Use \Drupal\Tests\SchemaCheckTestTrait instead.', E_USER_DEPRECATED);

/**
 * Provides a class for checking configuration schema.
 *
 * @deprecated as of 8.3.x, will be removed in before Drupal 9.0.0. Use
 *   \Drupal\Tests\SchemaCheckTestTrait instead.
 */
trait SchemaCheckTestTrait {

  use \Drupal\Tests\SchemaCheckTestTrait;

}
