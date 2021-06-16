<?php

namespace Drupal\Tests\workflows\Functional\Hal;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class WorkflowHalJsonCookieTest extends WorkflowHalJsonAnonTest {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
