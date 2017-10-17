<?php

namespace Drupal\Tests\hal\Functional\EntityResource\EntityViewMode;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityViewMode\EntityViewModeResourceTestBase;

/**
 * @group hal
 */
class EntityViewModeHalJsonCookieTest extends EntityViewModeResourceTestBase {

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
