<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeBlockTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests the availability of the syndicate block.
 */
class NodeBlockTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block');

  public static function getInfo() {
    return array(
      'name' => 'Block availability',
      'description' => 'Check if the syndicate block is available.',
      'group' => 'Node',
    );
  }

  function setUp() {
    parent::setUp();

    // Create a user and log in.
    $admin_user = $this->drupalCreateUser(array('administer blocks'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that the "Syndicate" block is shown when enabled.
   */
  public function testSyndicateBlock() {
    $block_id = 'node_syndicate_block';
    $default_theme = variable_get('theme_default', 'stark');

    $block = array(
      'title' => $this->randomName(8),
      'machine_name' => $this->randomName(8),
      'region' => 'sidebar_second',
    );

    // Enable the syndicate block.
    $this->drupalPost('admin/structure/block/manage/' . $block_id . '/' . $default_theme, $block, t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), 'Node syndicate block enabled.');

    // Confirm that the block's xpath is available.
    $xpath = $this->buildXPathQuery('//div[@id=:id]/*', array(':id' => 'block-' . strtr(strtolower($block['machine_name']), '-', '_')));
    $this->assertFieldByXPath($xpath, NULL, 'Syndicate block found.');
  }
}
