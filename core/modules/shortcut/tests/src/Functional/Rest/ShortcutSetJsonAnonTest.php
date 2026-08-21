<?php

declare(strict_types=1);

namespace Drupal\Tests\shortcut\Functional\Rest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Shortcut Set Json Anon.
 */
#[Group('rest')]
#[RunTestsInSeparateProcesses]
class ShortcutSetJsonAnonTest extends ShortcutSetResourceTestBase {

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
