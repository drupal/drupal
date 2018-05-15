<?php

namespace Drupal\Tests\entity_test\Functional\Rest;

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
