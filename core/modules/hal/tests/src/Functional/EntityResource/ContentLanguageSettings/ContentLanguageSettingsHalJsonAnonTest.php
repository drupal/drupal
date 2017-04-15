<?php

namespace Drupal\Tests\hal\Functional\EntityResource\ContentLanguageSettings;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\ContentLanguageSettings\ContentLanguageSettingsResourceTestBase;

/**
 * @group hal
 */
class ContentLanguageSettingsHalJsonAnonTest extends ContentLanguageSettingsResourceTestBase {

  use AnonResourceTestTrait;

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

}
