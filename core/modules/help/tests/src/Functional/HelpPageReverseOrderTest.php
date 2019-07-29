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
   * Strings to search for on admin/help, in order.
   *
   * These are reversed, due to the alter hook.
   *
   * @var string[]
   */
  protected $stringOrder = [
    'Tours guide you',
    'Module overviews are provided',
  ];

}
