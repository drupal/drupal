<?php

namespace Drupal\Tests\hal\Functional\EntityResource\ContentLanguageSettings;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\ContentLanguageSettings\ContentLanguageSettingsResourceTestBase;

/**
 * @group hal
 */
class ContentLanguageSettingsHalJsonBasicAuthTest extends ContentLanguageSettingsResourceTestBase {

  use BasicAuthResourceTestTrait;

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
