<?php

declare(strict_types=1);

namespace Drupal\Tests\help\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verify the order of the help page.
 *
 * @group help
 */
class HelpPageOrderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['help', 'help_page_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Strings to search for on admin/help, in order.
   *
   * @var string[]
   */
  protected $stringOrder = [
    'Module overviews are provided',
    'This description should appear',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and log in user.
    $account = $this->drupalCreateUser([
      'access help pages',
      'view the administration theme',
      'administer permissions',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Tests the order of the help page.
   */
  public function testHelp(): void {
    $pos = 0;
    $this->drupalGet('admin/help');
    $page_text = $this->getTextContent();
    foreach ($this->stringOrder as $item) {
      $new_pos = strpos($page_text, $item, $pos);
      $this->assertGreaterThan($pos, $new_pos, "Order of $item is not correct on help page");
      $pos = $new_pos;
    }
  }

}
