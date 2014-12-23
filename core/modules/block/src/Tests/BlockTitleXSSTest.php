<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockTitleXSSTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests block XSS in title.
 *
 * @group block
 */
class BlockTitleXSSTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('block', 'block_test');

  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('test_xss_title', array('label' => '<script>alert("XSS label");</script>'));
  }

  /**
   * Test XSS in title.
   */
  function testXSSInTitle() {
    \Drupal::state()->set('block_test.content', $this->randomMachineName());
    $this->drupalGet('');
    $this->assertNoRaw('<script>alert("XSS label");</script>', 'The block title was properly sanitized when rendered.');

    $this->drupalLogin($this->drupalCreateUser(array('administer blocks', 'access administration pages')));
    $default_theme = $this->config('system.theme')->get('default');
    $this->drupalGet('admin/structure/block/list/' . $default_theme);
    $this->assertNoRaw("<script>alert('XSS subject');</script>", 'The block title was properly sanitized in Block Plugin UI Admin page.');
  }

}
