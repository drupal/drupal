<?php

/**
 * @file
 * Definition of Drupal\forum\Tests\ForumIndexTest.
 */

namespace Drupal\forum\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the forum index listing.
 */
class ForumIndexTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'comment', 'forum');

  public static function getInfo() {
    return array(
      'name' => 'Forum index',
      'description' => 'Tests the forum index listing.',
      'group' => 'Forum',
    );
  }

  function setUp() {
    parent::setUp();

    // Create a test user.
    $web_user = $this->drupalCreateUser(array('create forum content', 'edit own forum content', 'edit any forum content', 'administer nodes'));
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the forum index for published and unpublished nodes.
   */
  function testForumIndexStatus() {

    $langcode = Language::LANGCODE_NOT_SPECIFIED;

    // The forum ID to use.
    $tid = 1;

    // Create a test node.
    $title = $this->randomName(20);
    $edit = array(
      "title" => $title,
      "body[$langcode][0][value]" => $this->randomName(200),
    );

    // Create the forum topic, preselecting the forum ID via a URL parameter.
    $this->drupalPost('node/add/forum/' . $tid, $edit, t('Save and publish'));

    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($title);
    $this->assertTrue(!empty($node), 'New forum node found in database.');

    // Verify that the node appears on the index.
    $this->drupalGet('forum/' . $tid);
    $this->assertText($title, 'Published forum topic appears on index.');

    // Unpublish the node.
    $this->drupalPost('node/' . $node->id() . '/edit', array(), t('Save and unpublish'));
    $this->drupalGet('node/' . $node->id());
    $this->assertText(t('Access denied'), 'Unpublished node is no longer accessible.');

    // Verify that the node no longer appears on the index.
    $this->drupalGet('forum/' . $tid);
    $this->assertNoText($title, 'Unpublished forum topic no longer appears on index.');
  }
}
