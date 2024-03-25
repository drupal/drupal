<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Functional\Rest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * Test workspace entities for unauthenticated JSON requests.
 *
 * @group workspaces
 */
class WorkspaceJsonAnonTest extends WorkspaceResourceTestBase {

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
