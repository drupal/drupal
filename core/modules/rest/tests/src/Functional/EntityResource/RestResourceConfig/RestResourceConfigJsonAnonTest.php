<?php

namespace Drupal\Tests\rest\Functional\EntityResource\RestResourceConfig;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group rest
 */
class RestResourceConfigJsonAnonTest extends RestResourceConfigResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

}
