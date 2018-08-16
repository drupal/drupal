<?php

namespace Drupal\Tests\language\Functional\Rest;

use Drupal\Tests\rest\Functional\BasicAuthResourceWithInterfaceTranslationTestTrait;

/**
 * @group rest
 */
class ContentLanguageSettingsJsonBasicAuthTest extends ContentLanguageSettingsResourceTestBase {

  use BasicAuthResourceWithInterfaceTranslationTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'basic_auth';

}
