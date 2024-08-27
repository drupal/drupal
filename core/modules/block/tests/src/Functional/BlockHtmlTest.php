<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests block HTML ID validity.
 *
 * @group block
 */
class BlockHtmlTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'block_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'administer blocks',
      'access administration pages',
    ]));

    // Enable the test_html block, to test HTML ID and attributes.
    \Drupal::state()->set('block_test.attributes', ['data-custom-attribute' => 'foo']);
    \Drupal::state()->set('block_test.content', $this->randomMachineName());
    $this->drupalPlaceBlock('test_html', ['id' => 'test_html_block']);

    // Enable a menu block, to test more complicated HTML.
    $this->drupalPlaceBlock('system_menu_block:admin', ['id' => 'test_menu_block']);
  }

  /**
   * Tests for valid HTML for a block.
   */
  public function testHtml(): void {
    $this->drupalGet('');

    // Ensure that a block's ID is converted to an HTML valid ID, and that
    // block-specific attributes are added to the same DOM element.
    $this->assertSession()->elementExists('xpath', '//div[@id="block-test-html-block" and @data-custom-attribute="foo"]');

    // Ensure expected markup for a menu block.
    $this->assertSession()->elementExists('xpath', '//nav[@id="block-test-menu-block"]/ul/li');
  }

}
