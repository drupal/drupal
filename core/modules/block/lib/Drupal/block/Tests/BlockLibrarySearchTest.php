<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockLibrarySearchTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the block library search.
 */
class BlockLibrarySearchTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block');

  /**
   * An administrative user to configure the test environment.
   */
  protected $adminUser;

  public static function getInfo() {
    return array(
      'name' => 'Block library search',
      'description' => 'Checks that the block library search works correctly.',
      'group' => 'Block',
    );
  }

  protected function setUp() {
    parent::setUp();
    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser(array(
      'administer blocks',
      'access administration pages',
    ));
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test block library search.
   */
  function testBlockLibrarySearch() {
    // Check that the block plugin is valid.
    $this->drupalPost('admin/structure/block/list/stark/add', array('block' => 'invalid_block'), t('Next'));
    $this->assertText('You must select a valid block.');

    // Check that the block search form redirects to the correct block form.
    $this->drupalPost('admin/structure/block/list/stark/add', array('block' => 'system_main_block'), t('Next'));
    $this->assertUrl('admin/structure/block/add/system_main_block/stark');
  }

}
