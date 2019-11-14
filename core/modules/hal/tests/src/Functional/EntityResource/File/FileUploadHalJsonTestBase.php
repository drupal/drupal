<?php

namespace Drupal\Tests\hal\Functional\EntityResource\File;

@trigger_error('The ' . __NAMESPACE__ . '\FileUploadHalJsonTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\file\Functional\Hal\FileUploadHalJsonTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\file\Functional\Hal\FileUploadHalJsonTestBase as FileUploadHalJsonTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\file\Functional\Hal\FileUploadHalJsonTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class FileUploadHalJsonTestBase extends FileUploadHalJsonTestBaseReal {
}
