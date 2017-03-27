<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Action;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\Action\ActionResourceTestBase;

/**
 * @group hal
 */
class ActionHalJsonCookieTest extends ActionResourceTestBase {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal'];

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
  protected static $auth = 'cookie';

}
