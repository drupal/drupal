<?php

namespace Drupal\Tests\hal\Functional\EntityResource\ContentLanguageSettings;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\ContentLanguageSettings\ContentLanguageSettingsResourceTestBase;

/**
 * @group hal
 */
class ContentLanguageSettingsHalJsonCookieTest extends ContentLanguageSettingsResourceTestBase {

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
