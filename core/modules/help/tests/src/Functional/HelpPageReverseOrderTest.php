<?php

namespace Drupal\Tests\help\Functional;

/**
 * Verify the order of the help page with an alter hook.
 *
 * @group help
 */
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
