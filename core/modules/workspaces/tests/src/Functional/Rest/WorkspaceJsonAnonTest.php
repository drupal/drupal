<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Functional\Rest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test workspace entities for unauthenticated JSON requests.
 */
#[Group('workspaces')]
#[RunTestsInSeparateProcesses]
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
