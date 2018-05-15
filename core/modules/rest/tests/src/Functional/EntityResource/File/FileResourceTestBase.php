<?php

namespace Drupal\Tests\rest\Functional\EntityResource\File;

@trigger_error('The ' . __NAMESPACE__ . '\FileResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\file\Functional\Rest\FileResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\file\Functional\Rest\FileResourceTestBase as FileResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\file\Functional\Rest\FileResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class FileResourceTestBase extends FileResourceTestBaseReal {
}
