<?php

namespace Drupal\Tests\hal\Functional\EntityResource\ConfigurableLanguage;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\ConfigurableLanguage\ConfigurableLanguageResourceTestBase;

/**
 * @group hal
 */
class ConfigurableLanguageHalJsonBasicAuthTest extends ConfigurableLanguageResourceTestBase {

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
