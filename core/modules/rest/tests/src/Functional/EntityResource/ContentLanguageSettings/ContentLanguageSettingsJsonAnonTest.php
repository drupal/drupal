<?php

namespace Drupal\Tests\rest\Functional\EntityResource\ContentLanguageSettings;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group rest
 */
class ContentLanguageSettingsJsonAnonTest extends ContentLanguageSettingsResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

}
