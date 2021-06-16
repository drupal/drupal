<?php

namespace Drupal\FunctionalTests\Rest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group rest
 */
class DateFormatJsonAnonTest extends DateFormatResourceTestBase {

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
