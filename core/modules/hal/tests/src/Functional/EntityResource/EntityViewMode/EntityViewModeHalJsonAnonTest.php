<?php

namespace Drupal\Tests\hal\Functional\EntityResource\EntityViewMode;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityViewMode\EntityViewModeResourceTestBase;

/**
 * @group hal
 */
class EntityViewModeHalJsonAnonTest extends EntityViewModeResourceTestBase {

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
