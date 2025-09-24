<?php

declare(strict_types=1);

namespace Drupal\Tests\path_alias\Functional\Rest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test path_alias entities for unauthenticated JSON requests.
 */
#[Group('path_alias')]
#[RunTestsInSeparateProcesses]
class PathAliasJsonAnonTest extends PathAliasResourceTestBase {

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
