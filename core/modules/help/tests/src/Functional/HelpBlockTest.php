<?php

namespace Drupal\Tests\help\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests display of help block.
 *
 * @group help
 */
class HelpBlockTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'help',
    'help_page_test',
    'block',
    'more_help_page_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The help block instance.
   *
   * @var \Drupal\block\Entity\Block
   */
  protected $helpBlock;

  protected function setUp(): void {
    parent::setUp();
    $this->helpBlock = $this->placeBlock('help_block');
  }

  /**
   * Logs in users, tests help pages.
   */
  public function testHelp() {
    $this->drupalGet('help_page_test/has_help');
    $this->assertSession()->pageTextContains('I have help!');
    $this->assertSession()->pageTextContains($this->helpBlock->label());

    $this->drupalGet('help_page_test/no_help');
    // The help block should not appear when there is no help.
    $this->assertSession()->pageTextNotContains($this->helpBlock->label());

    // Ensure that if two hook_help() implementations both return a render array
    // the output is as expected.
    $this->drupalGet('help_page_test/test_array');
    $this->assertSession()->pageTextContains('Help text from more_help_page_test_help module.');
    $this->assertSession()->pageTextContains('Help text from help_page_test_help module.');
  }

}
