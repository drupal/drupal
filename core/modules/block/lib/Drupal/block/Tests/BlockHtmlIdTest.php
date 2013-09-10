<?php

/**
 * @file
 * Definition of Drupal\block\Tests\BlockHtmlIdTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests block HTML ID validity.
 */
class BlockHtmlIdTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'block_test');

  public static function getInfo() {
    return array(
      'name' => 'Block HTML ID',
      'description' => 'Tests block HTML ID validity.',
      'group' => 'Block',
    );
  }

  function setUp() {
    parent::setUp();

    $this->drupalLogin($this->root_user);

    // Make sure the block has some content so it will appear.
    $current_content = $this->randomName();
    \Drupal::state()->set('block_test.content', $current_content);

    // Enable our test blocks.
    $this->drupalPlaceBlock('system_menu_block:tools');
    $this->drupalPlaceBlock('test_html_id', array('machine_name' => 'test_id_block'));
  }

  /**
   * Tests for a valid HTML ID for a block.
   */
  function testHtmlId() {
    $this->drupalGet('');
    $this->assertRaw('id="block-test-id-block"', 'HTML ID for test block is valid.');
    $elements = $this->xpath('//div[contains(@class, :div-class)]/div/ul[contains(@class, :ul-class)]/li', array(':div-class' => 'block-system', ':ul-class' => 'menu'));
    $this->assertTrue(!empty($elements), 'The proper block markup was found.');
  }

}
