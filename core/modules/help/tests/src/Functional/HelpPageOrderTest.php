<?php

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
  protected static $modules = ['help', 'tour'];

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
    'Tours guide you',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and log in user.
    $account = $this->drupalCreateUser([
      'access administration pages',
      'view the administration theme',
      'administer permissions',
      'access tour',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Tests the order of the help page.
   */
  public function testHelp() {
    $pos = 0;
    $this->drupalGet('admin/help');
    $page_text = $this->getTextContent();
    foreach ($this->stringOrder as $item) {
      $new_pos = strpos($page_text, $item, $pos);
      $this->assertTrue($new_pos > $pos, 'Order of ' . $item . ' is correct on help page');
      $pos = $new_pos;
    }
  }

}
