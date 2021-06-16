<?php

namespace Drupal\Tests\language\Functional\Rest;

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

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
