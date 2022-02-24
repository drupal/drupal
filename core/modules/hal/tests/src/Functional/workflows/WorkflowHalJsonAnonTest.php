<?php

namespace Drupal\Tests\hal\Functional\workflows;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\workflows\Functional\Rest\WorkflowResourceTestBase;

/**
 * @group hal
 * @group legacy
 */
class WorkflowHalJsonAnonTest extends WorkflowResourceTestBase {

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
