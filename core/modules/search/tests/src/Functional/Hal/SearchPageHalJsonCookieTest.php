<?php

namespace Drupal\Tests\search\Functional\Hal;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\search\Functional\Rest\SearchPageResourceTestBase;

/**
 * @group hal
 */
class SearchPageHalJsonCookieTest extends SearchPageResourceTestBase {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal'];

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
