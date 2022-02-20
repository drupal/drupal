<?php

namespace Drupal\Tests\hal\Functional\user;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\user\Functional\Rest\RoleResourceTestBase;

/**
 * @group hal
 */
class RoleHalJsonAnonTest extends RoleResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['hal'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $format = 'hal_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

}
