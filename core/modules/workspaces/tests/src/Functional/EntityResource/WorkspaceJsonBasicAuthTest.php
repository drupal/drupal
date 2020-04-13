<?php

namespace Drupal\Tests\workspaces\Functional\EntityResource;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;

/**
 * Test workspace entities for JSON requests via basic auth.
 *
 * @group workspaces
 */
class WorkspaceJsonBasicAuthTest extends WorkspaceResourceTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  protected static $auth = 'basic_auth';

}
