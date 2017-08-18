<?php

namespace Drupal\Tests\hal\Functional\EntityResource\DateFormat;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\DateFormat\DateFormatResourceTestBase;

/**
 * @group hal
 */
class DateFormatHalJsonAnonTest extends DateFormatResourceTestBase {

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
