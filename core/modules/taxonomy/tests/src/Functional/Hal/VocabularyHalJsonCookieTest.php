<?php

namespace Drupal\Tests\taxonomy\Functional\Hal;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\taxonomy\Functional\Rest\VocabularyResourceTestBase;

/**
 * @group hal
 */
class VocabularyHalJsonCookieTest extends VocabularyResourceTestBase {

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
