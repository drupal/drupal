<?php

namespace Drupal\simpletest\Exception;

@trigger_error(__NAMESPACE__ . '\\MissingGroupException is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Test\Exception\MissingGroupException instead. See https://www.drupal.org/node/2949692', E_USER_DEPRECATED);

use Drupal\Core\Test\Exception\MissingGroupException as CoreMissingGroupException;

/**
 * Exception thrown when a simpletest class is missing an @group annotation.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Core\Test\Exception\MissingGroupException instead.
 *
 * @see https://www.drupal.org/node/2949692
 */
class MissingGroupException extends CoreMissingGroupException {
}
