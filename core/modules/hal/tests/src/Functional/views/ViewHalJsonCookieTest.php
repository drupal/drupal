<?php

namespace Drupal\Tests\hal\Functional\views;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\views\Functional\Rest\ViewResourceTestBase;

/**
 * @group hal
 */
class ViewHalJsonCookieTest extends ViewResourceTestBase {

  use CookieResourceTestTrait;

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

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
