<?php

declare(strict_types=1);

namespace Drupal\Tests\help\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Verify the order of the help page with an alter hook.
 */
#[Group('help')]
#[RunTestsInSeparateProcesses]
class HelpPageReverseOrderTest extends HelpPageOrderTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['more_help_page_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Strings to search for on admin/help, in order.
   *
   * These are reversed, due to the alter hook.
   *
   * @var string[]
   */
  protected $stringOrder = [
    'This description should appear',
    'Module overviews are provided',
  ];

}
