<?php

namespace Drupal\Tests\contact\Functional\Rest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group rest
 */
class ContactFormJsonAnonTest extends ContactFormResourceTestBase {

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
