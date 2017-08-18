<?php

namespace Drupal\Tests\rest\Functional\EntityResource\ResponsiveImageStyle;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group rest
 */
class ResponsiveImageStyleJsonAnonTest extends ResponsiveImageStyleResourceTestBase {

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
