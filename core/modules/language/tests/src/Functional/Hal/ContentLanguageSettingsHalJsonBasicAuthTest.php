<?php

namespace Drupal\Tests\language\Functional\Hal;

use Drupal\Tests\language\Functional\Rest\ContentLanguageSettingsResourceTestBase;
use Drupal\Tests\rest\Functional\BasicAuthResourceWithInterfaceTranslationTestTrait;

/**
 * @group hal
 */
class ContentLanguageSettingsHalJsonBasicAuthTest extends ContentLanguageSettingsResourceTestBase {

  use BasicAuthResourceWithInterfaceTranslationTestTrait;

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
  protected static $auth = 'basic_auth';

}
