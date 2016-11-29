<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Role;

use Drupal\Tests\hal\Functional\HalJsonBasicAuthWorkaroundFor2805281Trait;
use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\Role\RoleResourceTestBase;

/**
 * @group hal
 */
class RoleHalJsonBasicAuthTest extends RoleResourceTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal', 'basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected static $format = 'hal_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

  /**
   * {@inheritdoc}
   */
  protected static $expectedErrorMimeType = 'application/json';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'basic_auth';

  // @todo Fix in https://www.drupal.org/node/2805281: remove this trait usage.
  use HalJsonBasicAuthWorkaroundFor2805281Trait {
    HalJsonBasicAuthWorkaroundFor2805281Trait::assertResponseWhenMissingAuthentication insteadof BasicAuthResourceTestTrait;
  }

}
