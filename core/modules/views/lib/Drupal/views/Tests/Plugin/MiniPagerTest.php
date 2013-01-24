<?php

/**
 * @file
 * Contains Drupal\views\Tests\Plugin\MiniPagerTest.
 */

namespace Drupal\views\Tests\Plugin;

/**
 * Tests the mini pager plugin
 *
 * @see \Drupal\views\Plugin\views\pager\Mini
 */
class MiniPagerTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_mini_pager');

  /**
   * Nodes used by the test.
   *
   * @var array
   */
  protected $nodes;

  public static function getInfo() {
    return array(
      'name' => 'Pager: Mini',
      'description' => 'Test the mini pager plugin.',
      'group' => 'Views Plugins',
    );
  }

  protected function setUp() {
    parent::setUp();

    // Create a bunch of test nodes.
    for ($i = 0; $i < 20; $i++) {
      $this->nodes[] = $this->drupalCreateNode();
    }
  }

  /**
   * Tests the rendering of mini pagers.
   */
  public function testMiniPagerRender() {
    menu_router_rebuild();
    $this->drupalGet('test_mini_pager');
    $this->assertText('›› test', 'Make sure the next link appears on the first page.');
    $this->assertNoText('‹‹ test', 'Make sure the previous link does not appear on the first page.');

    $this->drupalGet('test_mini_pager', array('query' => array('page' => 1)));
    $this->assertText('‹‹ test', 'Make sure the previous link appears.');
    $this->assertText('›› test', 'Make sure the next link appears.');

    $this->drupalGet('test_mini_pager', array('query' => array('page' => 6)));
    $this->assertNoText('›› test', 'Make sure the next link appears on the last page.');
    $this->assertText('‹‹ test', 'Make sure the previous link does not appear on the last page.');
  }

}
