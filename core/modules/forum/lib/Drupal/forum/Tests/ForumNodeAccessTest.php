<?php

/**
 * @file
 * Definition of Drupal\forum\Tests\ForumNodeAccessTest.
 */

namespace Drupal\forum\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Tests forum block view for private node access.
 */
class ForumNodeAccessTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'comment', 'forum', 'taxonomy', 'tracker', 'node_access_test', 'block');

  protected $access_user;
  protected $admin_user;
  protected $no_access_user;

  public static function getInfo() {
    return array(
      'name' => 'Forum private node access test',
      'description' => 'Tests forum block view for private node access',
      'group' => 'Forum',
    );
  }

  function setUp() {
    parent::setUp();
    node_access_rebuild();
    state()->set('node_access_test.private', TRUE);
  }

  /**
   * Creates some users and creates a public node and a private node.
   *
   * Adds both active forum topics and new forum topics blocks to the sidebar.
   * Tests to ensure private node/public node access is respected on blocks.
   */
  function testForumNodeAccess() {
    // Create some users.
    $access_user = $this->drupalCreateUser(array('node test view'));
    $no_access_user = $this->drupalCreateUser();
    $admin_user = $this->drupalCreateUser(array('access administration pages', 'administer modules', 'administer blocks', 'create forum content'));

    $this->drupalLogin($admin_user);

    // Create a private node.
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $private_node_title = $this->randomName(20);
    $edit = array(
      'title' => $private_node_title,
      "body[$langcode][0][value]" => $this->randomName(200),
      'private' => TRUE,
    );
    $this->drupalPost('node/add/forum/1', $edit, t('Save'));
    $private_node = $this->drupalGetNodeByTitle($private_node_title);
    $this->assertTrue(!empty($private_node), 'New private forum node found in database.');

    // Create a public node.
    $public_node_title = $this->randomName(20);
    $edit = array(
      'title' => $public_node_title,
      "body[$langcode][0][value]" => $this->randomName(200),
    );
    $this->drupalPost('node/add/forum/1', $edit, t('Save'));
    $public_node = $this->drupalGetNodeByTitle($public_node_title);
    $this->assertTrue(!empty($public_node), 'New public forum node found in database.');


    // Enable the new and active forum blocks.
    $this->drupalPlaceBlock('forum_active_block');
    $this->drupalPlaceBlock('forum_new_block');

    // Test for $access_user.
    $this->drupalLogin($access_user);
    $this->drupalGet('');

    // Ensure private node and public node are found.
    $this->assertText($private_node->title, 'Private node found in block by $access_user');
    $this->assertText($public_node->title, 'Public node found in block by $access_user');

    // Test for $no_access_user.
    $this->drupalLogin($no_access_user);
    $this->drupalGet('');

    // Ensure private node is not found but public is found.
    $this->assertNoText($private_node->title, 'Private node not found in block by $no_access_user');
    $this->assertText($public_node->title, 'Public node found in block by $no_access_user');
  }

}
