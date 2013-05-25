<?php

/**
 * @file
 * Definition of Drupal\block\Tests\BlockRenderOrderTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests block HTML ID validity.
 */
class BlockRenderOrderTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'block_test', 'search');

  public static function getInfo() {
    return array(
      'name' => 'Block Render Order',
      'description' => 'Test blocks are being rendered in order by weight.',
      'group' => 'Block',
    );
  }

  function setUp() {
    parent::setUp();
    // Create a test user.
    $end_user = $this->drupalCreateUser(array(
      'access content',
      'search content',
    ));
    $this->drupalLogin($end_user);
  }

  /**
   * Tests the render order of the blocks.
   */
  function testBlockRenderOrder() {
    //Enable test blocks and place them in the same region.
    $blocks = array(array($this->randomName(8), 'system_powered_by_block'), array($this->randomName(8), 'search_form_block'));
    foreach ($blocks as $weight => $settings) {
      $this->drupalPlaceBlock($settings[1], array(
        'label' => $settings[0],
        'weight' => $weight,
        'region' => 'header',
      ));
    }
    $this->drupalGet('');
    $test_content = $this->drupalGetContent('');
    foreach ($blocks as $weight => $settings) {
      $this->assertRaw('<h2>' . $settings[0] . '</h2>', 'Block "' . $settings[0]  . '" is in place.');
      $position[$weight] = strpos($test_content, $settings[0]);
    }
    $this->assertTrue($position[0] < $position[1], 'The blocks are rendered in the correct order.');
  }
}
