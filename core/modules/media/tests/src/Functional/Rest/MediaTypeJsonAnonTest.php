<?php

namespace Drupal\Tests\media\Functional\Rest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group rest
 */
class MediaTypeJsonAnonTest extends MediaTypeResourceTestBase {

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
