<?php

namespace Drupal\help\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests display of help block.
 *
 * @group help
 */
class HelpBlockTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['help', 'help_page_test', 'block', 'more_help_page_test'];

  /**
   * The help block instance.
   *
   * @var \Drupal\block\Entity\Block
   */
  protected $helpBlock;

  protected function setUp() {
    parent::setUp();
    $this->helpBlock = $this->placeBlock('help_block');
  }

  /**
   * Logs in users, tests help pages.
   */
  public function testHelp() {
    $this->drupalGet('help_page_test/has_help');
    $this->assertText(t('I have help!'));
    $this->assertText($this->helpBlock->label());

    $this->drupalGet('help_page_test/no_help');
    // The help block should not appear when there is no help.
    $this->assertNoText($this->helpBlock->label());

    // Ensure that if two hook_help() implementations both return a render array
    // the output is as expected.
    $this->drupalGet('help_page_test/test_array');
    $this->assertText('Help text from more_help_page_test_help module.');
    $this->assertText('Help text from help_page_test_help module.');
  }

}
