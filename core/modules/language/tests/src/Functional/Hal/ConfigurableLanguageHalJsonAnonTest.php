<?php

namespace Drupal\Tests\language\Functional\Hal;

use Drupal\Tests\language\Functional\Rest\ConfigurableLanguageResourceTestBase;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group hal
 */
class ConfigurableLanguageHalJsonAnonTest extends ConfigurableLanguageResourceTestBase {

  use AnonResourceTestTrait;

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

}
