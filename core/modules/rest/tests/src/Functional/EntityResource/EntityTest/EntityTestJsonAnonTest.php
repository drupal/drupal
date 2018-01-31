<?php

namespace Drupal\Tests\rest\Functional\EntityResource\EntityTest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\FormatSpecificGetBcRouteTestTrait;

/**
 * @group rest
 */
class EntityTestJsonAnonTest extends EntityTestResourceTestBase {

  use AnonResourceTestTrait;
  use FormatSpecificGetBcRouteTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

}
